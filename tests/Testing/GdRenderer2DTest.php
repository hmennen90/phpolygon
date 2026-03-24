<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Testing;

use PHPUnit\Framework\TestCase;
use PHPolygon\Math\Mat3;
use PHPolygon\Math\Vec2;
use PHPolygon\Rendering\Color;
use PHPolygon\Testing\GdRenderer2D;

class GdRenderer2DTest extends TestCase
{
    public function testCreateAndGetDimensions(): void
    {
        $renderer = new GdRenderer2D(800, 600);

        $this->assertSame(800, $renderer->getWidth());
        $this->assertSame(600, $renderer->getHeight());
    }

    public function testBeginFrameClearsToBlack(): void
    {
        $renderer = new GdRenderer2D(10, 10);
        $renderer->beginFrame();

        $img = $renderer->getImage();
        $pixel = imagecolorat($img, 5, 5);
        $r = ($pixel >> 16) & 0xFF;
        $g = ($pixel >> 8) & 0xFF;
        $b = $pixel & 0xFF;

        $this->assertSame(0, $r);
        $this->assertSame(0, $g);
        $this->assertSame(0, $b);
    }

    public function testClearFillsWithColor(): void
    {
        $renderer = new GdRenderer2D(10, 10);
        $renderer->beginFrame();
        $renderer->clear(new Color(1.0, 0.0, 0.0));

        $img = $renderer->getImage();
        $pixel = imagecolorat($img, 5, 5);
        $r = ($pixel >> 16) & 0xFF;

        $this->assertSame(255, $r);
    }

    public function testDrawRectFillsPixels(): void
    {
        $renderer = new GdRenderer2D(100, 100);
        $renderer->beginFrame();
        $renderer->drawRect(10, 10, 20, 20, new Color(0.0, 1.0, 0.0));

        $img = $renderer->getImage();

        // Inside rect
        $pixel = imagecolorat($img, 15, 15);
        $g = ($pixel >> 8) & 0xFF;
        $this->assertSame(255, $g);

        // Outside rect
        $pixel = imagecolorat($img, 5, 5);
        $g = ($pixel >> 8) & 0xFF;
        $this->assertSame(0, $g);
    }

    public function testDrawCircleFillsPixels(): void
    {
        $renderer = new GdRenderer2D(100, 100);
        $renderer->beginFrame();
        $renderer->drawCircle(50, 50, 20, new Color(0.0, 0.0, 1.0));

        $img = $renderer->getImage();

        // Center should be blue
        $pixel = imagecolorat($img, 50, 50);
        $b = $pixel & 0xFF;
        $this->assertSame(255, $b);
    }

    public function testDrawLineSetsPixels(): void
    {
        $renderer = new GdRenderer2D(100, 100);
        $renderer->beginFrame();
        $renderer->drawLine(
            new Vec2(0, 50),
            new Vec2(99, 50),
            new Color(1.0, 1.0, 1.0),
            1.0,
        );

        $img = $renderer->getImage();

        // On the line
        $pixel = imagecolorat($img, 50, 50);
        $r = ($pixel >> 16) & 0xFF;
        $this->assertGreaterThan(200, $r);
    }

    public function testPushPopTransform(): void
    {
        $renderer = new GdRenderer2D(100, 100);
        $renderer->beginFrame();

        // Draw rect at (10,10) with translation of (20,20) → effectively at (30,30)
        $renderer->pushTransform(Mat3::translation(20, 20));
        $renderer->drawRect(10, 10, 5, 5, new Color(1.0, 0.0, 0.0));
        $renderer->popTransform();

        $img = $renderer->getImage();

        // Should be red at (32, 32) which is inside the translated rect
        $pixel = imagecolorat($img, 32, 32);
        $r = ($pixel >> 16) & 0xFF;
        $this->assertSame(255, $r);

        // Original position (12, 12) should be black
        $pixel = imagecolorat($img, 12, 12);
        $r = ($pixel >> 16) & 0xFF;
        $this->assertSame(0, $r);
    }

    public function testSavePng(): void
    {
        $renderer = new GdRenderer2D(50, 50);
        $renderer->beginFrame();
        $renderer->drawRect(0, 0, 50, 50, new Color(1.0, 0.5, 0.0));
        $renderer->endFrame();

        $path = sys_get_temp_dir() . '/phpolygon-gd-test-' . getmypid() . '.png';
        $renderer->savePng($path);

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));

        // Verify it's a valid PNG
        $loaded = imagecreatefrompng($path);
        $this->assertNotFalse($loaded);
        $this->assertSame(50, imagesx($loaded));
        $this->assertSame(50, imagesy($loaded));

        unset($loaded);
        @unlink($path);
    }

    public function testSetViewportUpdatesSize(): void
    {
        $renderer = new GdRenderer2D(800, 600);
        $renderer->setViewport(0, 0, 320, 240);

        $this->assertSame(320, $renderer->getWidth());
        $this->assertSame(240, $renderer->getHeight());
    }

    public function testLoadAndSetFont(): void
    {
        $renderer = new GdRenderer2D(200, 100);
        $renderer->beginFrame();

        // Loading a non-existent font shouldn't crash
        $renderer->loadFont('test', '/nonexistent/font.ttf');
        $renderer->setFont('test');

        // Drawing text with bad font path falls through to imagettftext
        // which will silently fail — no crash expected
        $renderer->drawText('Hello', 10, 10, 16, new Color(1.0, 1.0, 1.0));

        $this->assertTrue(true); // No exception = pass
    }

    public function testDrawRoundedRect(): void
    {
        $renderer = new GdRenderer2D(100, 100);
        $renderer->beginFrame();
        $renderer->drawRoundedRect(10, 10, 80, 80, 10, new Color(0.0, 1.0, 0.0));

        $img = $renderer->getImage();

        // Center should be green
        $pixel = imagecolorat($img, 50, 50);
        $g = ($pixel >> 8) & 0xFF;
        $this->assertSame(255, $g);
    }

    public function testNestedTransforms(): void
    {
        $renderer = new GdRenderer2D(200, 200);
        $renderer->beginFrame();

        $renderer->pushTransform(Mat3::translation(50, 0));
        $renderer->pushTransform(Mat3::translation(0, 50));
        // Effective: translate(50, 50)
        $renderer->drawRect(0, 0, 10, 10, new Color(1.0, 1.0, 1.0));
        $renderer->popTransform();
        $renderer->popTransform();

        $img = $renderer->getImage();

        // Should have white at (55, 55)
        $pixel = imagecolorat($img, 55, 55);
        $r = ($pixel >> 16) & 0xFF;
        $this->assertSame(255, $r);

        // Origin should still be black
        $pixel = imagecolorat($img, 5, 5);
        $r = ($pixel >> 16) & 0xFF;
        $this->assertSame(0, $r);
    }
}
