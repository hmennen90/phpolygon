<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Testing;

use PHPUnit\Framework\TestCase;
use PHPolygon\Math\Vec2;
use PHPolygon\Rendering\Color;
use PHPolygon\Testing\GdRenderer2D;
use PHPolygon\Testing\VisualTestCase;

/**
 * End-to-end visual regression test demonstrating the VRT workflow.
 *
 * Run normally:    vendor/bin/phpunit tests/Testing/VisualRegressionTest.php
 * Update snaps:    PHPOLYGON_UPDATE_SNAPSHOTS=1 vendor/bin/phpunit tests/Testing/VisualRegressionTest.php
 */
class VisualRegressionTest extends TestCase
{
    use VisualTestCase;

    public function testBasicShapesScreenshot(): void
    {
        $renderer = new GdRenderer2D(400, 300);
        $renderer->beginFrame();

        // Background
        $renderer->clear(new Color(0.1, 0.1, 0.15));

        // Red rectangle
        $renderer->drawRect(20, 20, 120, 80, new Color(0.9, 0.2, 0.2));

        // Green circle
        $renderer->drawCircle(250, 60, 40, new Color(0.2, 0.8, 0.3));

        // Blue rounded rect
        $renderer->drawRoundedRect(20, 130, 160, 60, 12, new Color(0.2, 0.4, 0.9));

        // White line
        $renderer->drawLine(new Vec2(200, 130), new Vec2(380, 200), new Color(1.0, 1.0, 1.0), 2.0);

        // Yellow rect outline
        $renderer->drawRectOutline(200, 210, 180, 70, new Color(1.0, 0.9, 0.2), 2.0);

        // Circle outline
        $renderer->drawCircleOutline(100, 250, 30, new Color(0.8, 0.4, 0.9), 2.0);

        $renderer->endFrame();

        $this->assertScreenshot($renderer, 'basic-shapes');
    }

    public function testColorGradientScreenshot(): void
    {
        $renderer = new GdRenderer2D(400, 100);
        $renderer->beginFrame();
        $renderer->clear(new Color(0.0, 0.0, 0.0));

        // Draw a horizontal gradient using thin vertical stripes
        for ($x = 0; $x < 400; $x++) {
            $t = $x / 399.0;
            $renderer->drawRect(
                (float) $x, 0, 1, 100,
                new Color($t, 0.0, 1.0 - $t),
            );
        }

        $renderer->endFrame();

        $this->assertScreenshot($renderer, 'color-gradient');
    }

    public function testOverlappingShapesScreenshot(): void
    {
        $renderer = new GdRenderer2D(300, 300);
        $renderer->beginFrame();
        $renderer->clear(new Color(0.05, 0.05, 0.05));

        // Three overlapping circles (additive-style)
        $renderer->drawCircle(120, 120, 80, new Color(0.8, 0.0, 0.0, 0.7));
        $renderer->drawCircle(180, 120, 80, new Color(0.0, 0.8, 0.0, 0.7));
        $renderer->drawCircle(150, 180, 80, new Color(0.0, 0.0, 0.8, 0.7));

        $renderer->endFrame();

        $this->assertScreenshot($renderer, 'overlapping-shapes');
    }
}
