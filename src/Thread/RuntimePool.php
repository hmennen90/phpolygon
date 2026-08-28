<?php

declare(strict_types=1);

namespace PHPolygon\Thread;

/**
 * Reusable pool of `parallel\Runtime` threads for one-shot fork/join work —
 * load-time bakes, asset rasterisation, anything that fans a pure-data job out
 * over cores once and joins the results.
 *
 * This is the counterpart to {@see ThreadScheduler}: the scheduler owns the
 * per-frame subsystem pipeline (one long-lived thread per subsystem, talking
 * over named Channels), while this pool owns short bursts of parallel work that
 * happen a handful of times per session.
 *
 * ## Why a pool
 *
 * Spawning a Runtime is cheap; spawning one that can autoload project classes
 * is NOT. Measured on PHP 8.5.5 ZTS / Windows, 7 Runtimes:
 *
 *   without bootstrap:            7.2 ms   ( 1.0 ms each)
 *   with vendor/autoload.php:   141.5 ms   (20.2 ms each)   ← 19.6x
 *   task on already-warm pool:    0.8 ms
 *
 * Every caller that writes `new \parallel\Runtime($bootstrap)` inline therefore
 * pays ~20 ms per worker *per call site, per invocation*, and — unless it also
 * calls `close()` — leaks the thread until the process exits. Routing that work
 * through the shared pool pays the bootstrap once for the whole session.
 *
 * ## What this does NOT do
 *
 * It does not give you shared memory. `parallel` runtimes are share-nothing:
 * every argument is COPIED into the worker and every result COPIED back, so a
 * job whose payload dwarfs its arithmetic will not get faster here. Keep
 * payloads flat (arrays of scalars, or — measurably better — binary strings via
 * `pack()`/`unpack()`, which move roughly 6x faster than the equivalent PHP
 * array) and return the smallest thing that answers the question.
 *
 * ## Usage
 *
 * ```php
 * $slabs = [[0, 12], [12, 24], [24, 36]];
 * $parts = RuntimePool::shared()->map(
 *     $slabs,
 *     static fn(array $range, string $grid): string
 *         => Baker::computeSlab($grid, $range[0], $range[1]),
 *     [$packedGrid],
 * );
 * ```
 *
 * The task MUST be a static closure: `parallel` rejects closures bound to `$this`
 * ({@see \parallel\Runtime\Error\IllegalInstruction}), and it may only receive and
 * return values that cross the thread boundary (arrays, strings, scalars, null).
 */
final class RuntimePool
{
    private static ?self $shared = null;

    /** @var array<int, \parallel\Runtime> Lazily grown — index i is spawned on first use. */
    private array $runtimes = [];

    private readonly int $size;

    /**
     * @param int $size Maximum worker threads; 0 (default) uses
     *                  {@see ParallelCapability::getRecommendedThreadCount()}.
     */
    public function __construct(int $size = 0)
    {
        $this->size = $size > 0 ? $size : ParallelCapability::getRecommendedThreadCount();
    }

    /**
     * Process-wide pool. Prefer this over constructing your own so unrelated
     * bakes share the same warm threads instead of each paying the bootstrap.
     */
    public static function shared(): self
    {
        return self::$shared ??= new self();
    }

    /**
     * Run $task once per item, in parallel, and return the results in input order.
     *
     * Falls back to running everything synchronously — same order, same results —
     * when ext-parallel is unavailable, when the pool is asked for a single item,
     * or when spawning/dispatch fails for an infrastructural reason (a Runtime or
     * Channel error). An exception thrown by the task ITSELF is not a reason to
     * retry serially: it would just throw again. It propagates instead, and
     * `parallel` rethrows it on the joining thread with its original type and
     * message intact, so a bug in a worker looks like a bug, not like a hang.
     *
     * The task is called as `$task($item, ...$sharedArgs)`. Its signature is
     * deliberately left untyped here: a native callable type cannot express
     * "one item plus this call's own required extra parameters", and pinning one
     * would reject every caller that uses $sharedArgs.
     *
     * @param  list<mixed> $items      One job per entry.
     * @param  \Closure    $task       Static closure; see the class docblock.
     * @param  list<mixed> $sharedArgs Appended to every call. Copied per job —
     *                                 keep them small or packed.
     * @return list<mixed> One result per item, in input order.
     */
    public function map(array $items, \Closure $task, array $sharedArgs = []): array
    {
        if ($items === []) {
            return [];
        }

        // One job, or no threading available: the spawn would cost more than the work.
        if (count($items) === 1 || !ParallelCapability::isAvailable()) {
            return self::runSerially($items, $task, $sharedArgs);
        }

        try {
            $workers = min(count($items), $this->size);
            $futures = [];
            $i = 0;
            foreach ($items as $item) {
                // run() returns null for a task that declares no return value;
                // such a job contributes null to keep results aligned with input.
                $futures[] = $this->runtime($i % $workers)->run($task, [$item, ...$sharedArgs]);
                $i++;
            }

            $results = [];
            foreach ($futures as $future) {
                $results[] = $future?->value();
            }

            return $results;
        } catch (\parallel\Runtime\Error | \parallel\Channel\Error $e) {
            // Infrastructural failure (bootstrap, closed runtime, illegal payload):
            // drop the pool so the next call re-spawns cleanly, then do the work.
            $this->close();

            return self::runSerially($items, $task, $sharedArgs);
        }
    }

    /**
     * @param  array<array-key, mixed> $items
     * @param  list<mixed>             $sharedArgs
     * @return list<mixed>
     */
    private static function runSerially(array $items, \Closure $task, array $sharedArgs): array
    {
        $results = [];
        foreach ($items as $item) {
            $results[] = $task($item, ...$sharedArgs);
        }

        return $results;
    }

    /**
     * The worker at $index, spawned on first use. Growing lazily means a two-chunk
     * job never pays for eight threads.
     */
    private function runtime(int $index): \parallel\Runtime
    {
        if (isset($this->runtimes[$index])) {
            return $this->runtimes[$index];
        }

        $bootstrap = ParallelCapability::autoloadBootstrap();

        return $this->runtimes[$index] = $bootstrap !== null
            ? new \parallel\Runtime($bootstrap)
            : new \parallel\Runtime();
    }

    /**
     * Join and release every spawned thread. Safe to call repeatedly; the pool
     * re-spawns on the next {@see map()}. The engine calls this on shutdown —
     * without it the threads stay alive until the process exits.
     */
    public function close(): void
    {
        foreach ($this->runtimes as $runtime) {
            try {
                $runtime->close();
            } catch (\parallel\Runtime\Error $e) {
                // Already closed or killed — nothing left to release.
            }
        }

        $this->runtimes = [];
    }

    /** Threads currently spawned (grows lazily up to {@see size()}). */
    public function activeCount(): int
    {
        return count($this->runtimes);
    }

    /** Maximum worker threads this pool will spawn. */
    public function size(): int
    {
        return $this->size;
    }

    /** Release the process-wide pool, if one was ever created. */
    public static function closeShared(): void
    {
        self::$shared?->close();
        self::$shared = null;
    }
}
