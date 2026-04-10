<?php

/**
 * PHPolygon -- vio 2D rendering demo
 *
 * Tests all Renderer2DInterface features via the vio backend:
 *   Rect, RoundedRect, Circle, Line, Text, Transform, Scissor
 *
 * Requires: extension=vio
 * Run: php examples/vio_2d_demo.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPolygon\Engine;
use PHPolygon\EngineConfig;
use PHPolygon\Math\Mat3;
use PHPolygon\Math\Vec2;
use PHPolygon\Rendering\Color;

if (!extension_loaded('vio')) {
    echo "This example requires the vio extension.\n";
    echo "Load it with: php -d extension=vio examples/vio_2d_demo.php\n";
    exit(1);
}

$engine = new Engine(new EngineConfig(
    title: 'PHPolygon -- vio 2D Demo',
    width: 1024,
    height: 768,
));

$time = 0.0;

$engine->onUpdate(function (Engine $engine, float $dt) use (&$time): void {
    $time += $dt;

    if ($engine->input->isKeyPressed(256)) { // ESC
        $engine->stop();
    }
});

$engine->onRender(function (Engine $engine) use (&$time): void {
    $r = $engine->renderer2D;

    $r->clear(new Color(0.12, 0.12, 0.15));

    // -- Shapes --
    $r->drawRect(30, 30, 120, 80, new Color(0.8, 0.2, 0.2));
    $r->drawRectOutline(30, 30, 120, 80, new Color(1.0, 1.0, 1.0), 2.0);

    $r->drawRoundedRect(180, 30, 120, 80, 12.0, new Color(0.2, 0.6, 0.9));
    $r->drawRoundedRectOutline(180, 30, 120, 80, 12.0, new Color(1.0, 1.0, 1.0), 2.0);

    $r->drawCircle(400, 70, 35, new Color(0.2, 0.8, 0.3));
    $r->drawCircleOutline(400, 70, 35, new Color(1.0, 1.0, 1.0), 2.0);

    $r->drawLine(new Vec2(470, 30), new Vec2(570, 110), new Color(1.0, 0.8, 0.0), 3.0);

    // -- Text --
    $r->drawText('Hello from vio!', 30, 140, 28, new Color(1.0, 1.0, 1.0));
    $r->drawTextCentered('Centered', $r->getWidth() / 2, $r->getHeight() / 2, 22, new Color(0.7, 0.7, 0.7));
    $r->drawTextBox(
        'This text wraps within a 200px box. The vio backend handles word-wrapping via vio_text_measure.',
        30, 220, 200.0, 16, new Color(0.9, 0.9, 0.8),
    );

    // -- Transform: rotating rect --
    $cx = $r->getWidth() / 2;
    $cy = $r->getHeight() * 0.6;
    $angle = $time * 1.5;
    $cos = cos($angle);
    $sin = sin($angle);
    $r->pushTransform(new Mat3([
        $cos, $sin, 0,
        -$sin, $cos, 0,
        $cx - $cx * $cos + $cy * $sin, $cy - $cx * $sin - $cy * $cos, 1,
    ]));
    $r->drawRect($cx - 40, $cy - 25, 80, 50, new Color(0.9, 0.4, 0.1));
    $r->drawText('Spinning', $cx - 30, $cy - 8, 16, new Color(1.0, 1.0, 1.0));
    $r->popTransform();

    // -- Scissor: clipped content --
    $r->drawText('Scissor test:', 30, 350, 18, new Color(0.6, 0.6, 0.6));
    $r->drawRectOutline(30, 375, 150, 60, new Color(1.0, 1.0, 0.0), 1.0);
    $r->pushScissor(30, 375, 150, 60);
    $r->drawRect(20, 370, 200, 80, new Color(0.5, 0.2, 0.7));
    $r->drawText('Clipped to box', 35, 395, 16, new Color(1.0, 1.0, 1.0));
    $r->popScissor();

    // -- Input feedback --
    $mx = $engine->input->getMouseX();
    $my = $engine->input->getMouseY();
    $r->drawCircle($mx, $my, 6, new Color(1.0, 1.0, 1.0, 0.5));
    $r->drawText(sprintf('Mouse: %.0f, %.0f', $mx, $my), 30, $r->getHeight() - 28, 14, new Color(0.5, 0.5, 0.5));

    $typed = $engine->input->getTextInput();
    if ($typed !== '') {
        $r->drawText('Typed: ' . $typed, 300, $r->getHeight() - 28, 14, new Color(0.8, 0.8, 0.2));
    }
});

$engine->run();
