<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Testing;

use PHPUnit\Framework\TestCase;
use PHPolygon\Testing\ScreenshotComparer;

class ScreenshotComparerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phpolygon-vrt-test-' . getmypid();
        @mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testIdenticalImagesMatch(): void
    {
        $img = $this->createSolidImage(100, 100, 255, 0, 0);
        $path1 = $this->tempDir . '/a.png';
        $path2 = $this->tempDir . '/b.png';
        imagepng($img, $path1);
        imagepng($img, $path2);
        unset($img);

        $result = ScreenshotComparer::compare($path1, $path2);

        $this->assertTrue($result->match);
        $this->assertSame(0, $result->mismatchedPixels);
        $this->assertSame(10000, $result->totalPixels);
        $this->assertSame(0.0, $result->mismatchRatio);
        $this->assertNull($result->error);
    }

    public function testDifferentImagesDoNotMatch(): void
    {
        $img1 = $this->createSolidImage(100, 100, 255, 0, 0);
        $img2 = $this->createSolidImage(100, 100, 0, 0, 255);
        $path1 = $this->tempDir . '/a.png';
        $path2 = $this->tempDir . '/b.png';
        $diffPath = $this->tempDir . '/diff.png';
        imagepng($img1, $path1);
        imagepng($img2, $path2);
        unset($img1, $img2);

        $result = ScreenshotComparer::compare($path1, $path2, $diffPath);

        $this->assertFalse($result->match);
        $this->assertSame(10000, $result->mismatchedPixels);
        $this->assertSame(1.0, $result->mismatchRatio);
        $this->assertNull($result->error);
        $this->assertFileExists($diffPath);
    }

    public function testPartialDifferenceShowsCorrectCount(): void
    {
        // Create two images, same except for a 10x10 area
        $img1 = $this->createSolidImage(100, 100, 128, 128, 128);
        $img2 = $this->createSolidImage(100, 100, 128, 128, 128);

        // Change a 10x10 block in img2
        $red = imagecolorallocate($img2, 255, 0, 0);
        imagefilledrectangle($img2, 0, 0, 9, 9, $red);

        $path1 = $this->tempDir . '/a.png';
        $path2 = $this->tempDir . '/b.png';
        imagepng($img1, $path1);
        imagepng($img2, $path2);
        unset($img1, $img2);

        $result = ScreenshotComparer::compare($path1, $path2);

        $this->assertFalse($result->match);
        $this->assertSame(100, $result->mismatchedPixels);
        $this->assertEqualsWithDelta(0.01, $result->mismatchRatio, 0.001);
    }

    public function testDimensionMismatchReturnsError(): void
    {
        $img1 = $this->createSolidImage(100, 100, 0, 0, 0);
        $img2 = $this->createSolidImage(200, 100, 0, 0, 0);
        $path1 = $this->tempDir . '/a.png';
        $path2 = $this->tempDir . '/b.png';
        imagepng($img1, $path1);
        imagepng($img2, $path2);
        unset($img1, $img2);

        $result = ScreenshotComparer::compare($path1, $path2);

        $this->assertFalse($result->match);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('dimensions differ', $result->error);
    }

    public function testMissingReferenceReturnsError(): void
    {
        $path = $this->tempDir . '/actual.png';
        $img = $this->createSolidImage(10, 10, 0, 0, 0);
        imagepng($img, $path);
        unset($img);

        $result = ScreenshotComparer::compare($this->tempDir . '/nonexistent.png', $path);

        $this->assertFalse($result->match);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('not found', $result->error);
    }

    public function testThresholdAffectsSensitivity(): void
    {
        // Create two slightly different images
        $img1 = $this->createSolidImage(10, 10, 128, 128, 128);
        $img2 = $this->createSolidImage(10, 10, 130, 130, 130);
        $path1 = $this->tempDir . '/a.png';
        $path2 = $this->tempDir . '/b.png';
        imagepng($img1, $path1);
        imagepng($img2, $path2);
        unset($img1, $img2);

        // Strict threshold should detect difference
        $strict = ScreenshotComparer::compare($path1, $path2, null, 0.0);
        $this->assertFalse($strict->match);

        // Lax threshold should pass
        $lax = ScreenshotComparer::compare($path1, $path2, null, 0.5);
        $this->assertTrue($lax->match);
    }

    public function testComparisonResultPassesWithTolerance(): void
    {
        $img1 = $this->createSolidImage(100, 100, 128, 128, 128);
        $img2 = $this->createSolidImage(100, 100, 128, 128, 128);
        $red = imagecolorallocate($img2, 255, 0, 0);
        imagefilledrectangle($img2, 0, 0, 4, 4, $red); // 25 pixels differ
        $path1 = $this->tempDir . '/a.png';
        $path2 = $this->tempDir . '/b.png';
        imagepng($img1, $path1);
        imagepng($img2, $path2);
        unset($img1, $img2);

        $result = ScreenshotComparer::compare($path1, $path2);

        // Exact match fails
        $this->assertFalse($result->passes());

        // But with tolerance it passes
        $this->assertTrue($result->passes(maxDiffPixels: 50));
        $this->assertTrue($result->passes(maxDiffPixelRatio: 0.01));

        // Too-strict tolerance still fails
        $this->assertFalse($result->passes(maxDiffPixels: 10));
    }

    public function testSummaryMessages(): void
    {
        $img = $this->createSolidImage(10, 10, 0, 0, 0);
        $path = $this->tempDir . '/a.png';
        imagepng($img, $path);
        unset($img);

        $match = ScreenshotComparer::compare($path, $path);
        $this->assertStringContainsString('Match', $match->summary());

        $mismatch = ScreenshotComparer::compare($path, $this->tempDir . '/missing.png');
        $this->assertStringContainsString('Error', $mismatch->summary());
    }

    private function createSolidImage(int $w, int $h, int $r, int $g, int $b): \GdImage
    {
        $img = imagecreatetruecolor($w, $h);
        $color = imagecolorallocate($img, $r, $g, $b);
        imagefilledrectangle($img, 0, 0, $w - 1, $h - 1, $color);
        return $img;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
