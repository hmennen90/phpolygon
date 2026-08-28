<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Thread;

use PHPUnit\Framework\TestCase;
use PHPolygon\Thread\ParallelCapability;
use PHPolygon\Thread\RuntimePool;

class RuntimePoolTest extends TestCase
{
    protected function tearDown(): void
    {
        RuntimePool::closeShared();
    }

    public function testEmptyInputReturnsEmptyAndSpawnsNothing(): void
    {
        $pool = new RuntimePool(4);

        $this->assertSame([], $pool->map([], static fn(int $n): int => $n * 2));
        $this->assertSame(0, $pool->activeCount());
    }

    /**
     * A single job is not worth a thread: spawning one costs ~20 ms with the
     * Composer bootstrap, far more than any job small enough to be alone.
     */
    public function testSingleItemRunsSeriallyWithoutSpawning(): void
    {
        $pool = new RuntimePool(4);

        $this->assertSame([21], $pool->map([7], static fn(int $n): int => $n * 3));
        $this->assertSame(0, $pool->activeCount());
    }

    public function testResultsKeepInputOrder(): void
    {
        $pool = new RuntimePool(3);
        $items = [1, 2, 3, 4, 5, 6];

        $this->assertSame(
            [1, 4, 9, 16, 25, 36],
            $pool->map($items, static fn(int $n): int => $n * $n),
        );
    }

    public function testSharedArgumentsReachEveryJob(): void
    {
        $pool = new RuntimePool(2);

        $this->assertSame(
            [11, 12, 13],
            $pool->map([1, 2, 3], static fn(int $n, int $base): int => $n + $base, [10]),
        );
    }

    public function testPacksAndUnpacksBinaryPayloads(): void
    {
        $pool = new RuntimePool(2);
        $chunks = [pack('f*', 1.0, 2.0), pack('f*', 3.0, 4.0)];

        $out = $pool->map($chunks, static function (string $chunk): string {
            $v = unpack('f*', $chunk);
            return pack('f*', ...array_map(static fn(float $f): float => $f * 2.0, $v));
        });

        $this->assertSame([2.0, 4.0], array_values(unpack('f*', $out[0])));
        $this->assertSame([6.0, 8.0], array_values(unpack('f*', $out[1])));
    }

    public function testWorkerCountIsCappedBySizeAndGrowsLazily(): void
    {
        if (!ParallelCapability::isAvailable()) {
            $this->markTestSkipped('ext-parallel not available — serial fallback spawns nothing.');
        }

        $pool = new RuntimePool(2);

        // Two jobs, cap of two → at most two threads.
        $pool->map([1, 2], static fn(int $n): int => $n);
        $this->assertSame(2, $pool->activeCount());

        // Six jobs must NOT grow the pool beyond its cap.
        $pool->map([1, 2, 3, 4, 5, 6], static fn(int $n): int => $n);
        $this->assertSame(2, $pool->activeCount());
    }

    public function testThreadsAreReusedAcrossCalls(): void
    {
        if (!ParallelCapability::isAvailable()) {
            $this->markTestSkipped('ext-parallel not available.');
        }

        $pool = new RuntimePool(2);
        $pool->map([1, 2], static fn(int $n): int => $n);
        $warm = $pool->activeCount();

        // The whole point of the pool: a second call re-uses the warm threads
        // instead of paying the autoload bootstrap again.
        $pool->map([3, 4], static fn(int $n): int => $n);

        $this->assertSame($warm, $pool->activeCount());
    }

    public function testCloseReleasesThreadsAndPoolStaysUsable(): void
    {
        if (!ParallelCapability::isAvailable()) {
            $this->markTestSkipped('ext-parallel not available.');
        }

        $pool = new RuntimePool(2);
        $pool->map([1, 2], static fn(int $n): int => $n);
        $this->assertGreaterThan(0, $pool->activeCount());

        $pool->close();
        $this->assertSame(0, $pool->activeCount());

        // Re-spawns transparently.
        $this->assertSame([2, 4], $pool->map([1, 2], static fn(int $n): int => $n * 2));
    }

    public function testCloseIsIdempotent(): void
    {
        $pool = new RuntimePool(2);
        $pool->close();
        $pool->close();

        $this->assertSame(0, $pool->activeCount());
    }

    /**
     * A throwing task is a bug, not an infrastructure problem — retrying it
     * serially would only throw again, so it must surface instead of being
     * swallowed into a silent double-run.
     */
    public function testTaskExceptionPropagatesInsteadOfFallingBackSerially(): void
    {
        if (!ParallelCapability::isAvailable()) {
            $this->markTestSkipped('ext-parallel not available.');
        }

        $pool = new RuntimePool(2);

        // parallel rethrows the worker's own exception on the joining thread,
        // so the original type and message survive the boundary.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('task blew up');
        $pool->map([1, 2], static function (int $n): int {
            throw new \RuntimeException('task blew up');
        });
    }

    public function testSharedPoolIsReturnedRepeatedly(): void
    {
        $this->assertSame(RuntimePool::shared(), RuntimePool::shared());
    }

    public function testDefaultSizeFollowsRecommendedThreadCount(): void
    {
        $this->assertSame(
            ParallelCapability::getRecommendedThreadCount(),
            (new RuntimePool())->size(),
        );
    }

    public function testExplicitSizeWins(): void
    {
        $this->assertSame(3, (new RuntimePool(3))->size());
    }
}
