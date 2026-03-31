<?php

/**
 * PHPolygon — Metal lighting showcase
 *
 * A single box lit by three coloured point lights that orbit around it at
 * different speeds and heights. Demonstrates AddPointLight and dynamic scenes.
 *
 *   Red light   — fast orbit, low altitude
 *   Green light — medium orbit, mid altitude
 *   Blue light  — slow orbit, high altitude
 *
 * Run: php examples/metal_lighting.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPolygon\Component\Camera3DComponent;
use PHPolygon\Component\DirectionalLight;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\Transform3D;
use PHPolygon\Engine;
use PHPolygon\EngineConfig;
use PHPolygon\Geometry\BoxMesh;
use PHPolygon\Geometry\MeshRegistry;
use PHPolygon\Math\Vec3;
use PHPolygon\Rendering\Color;
use PHPolygon\Rendering\Material;
use PHPolygon\Rendering\MaterialRegistry;
use PHPolygon\Rendering\Command\AddPointLight;
use PHPolygon\Rendering\Command\SetSkyColors;
use PHPolygon\Rendering\RenderCommandList;
use PHPolygon\System\Camera3DSystem;
use PHPolygon\System\Renderer3DSystem;

$engine = new Engine(new EngineConfig(
    title:           'PHPolygon — Metal lighting',
    width:           1280,
    height:          720,
    is3D:            true,
    renderBackend3D: 'metal',
));

$time = 0.0;

$engine->onInit(function () use ($engine): void {
    MeshRegistry::register('box', BoxMesh::generate(1.0, 1.0, 1.0));

    MaterialRegistry::register('concrete', new Material(
        albedo:    new Color(0.6, 0.58, 0.55),
        roughness: 0.85,
        metallic:  0.0,
    ));

    $commandList = $engine->commandList3D ?? new RenderCommandList();
    $engine->world->addSystem(new Camera3DSystem($commandList, 1280, 720));
    $engine->world->addSystem(new Renderer3DSystem($engine->renderer3D, $commandList));

    $camera = $engine->world->createEntity();
    $camera->attach(new Camera3DComponent(fov: 60.0, active: true));
    $camera->attach(new Transform3D(position: new Vec3(0.0, 2.5, 6.0)));

    // Very dim directional so the coloured lights dominate
    $ambient = $engine->world->createEntity();
    $ambient->attach(new DirectionalLight(
        direction: new Vec3(0.0, -1.0, 0.0),
        color:     new Color(1.0, 1.0, 1.0),
        intensity: 0.05,
    ));
    $ambient->attach(new Transform3D());

    $box = $engine->world->createEntity();
    $box->attach(new MeshRenderer('box', 'concrete'));
    $box->attach(new Transform3D(position: new Vec3(0.0, 0.0, 0.0)));
});

$engine->onUpdate(function (Engine $engine, float $dt) use (&$time): void {
    $time += $dt;
    $cl = $engine->commandList3D;
    if ($cl === null) {
        return;
    }

    $cl->add(new SetSkyColors(
        skyColor:     new Color(0.02, 0.02, 0.04),
        horizonColor: new Color(0.05, 0.05, 0.08),
    ));

    // Red — fast, low
    $cl->add(new AddPointLight(
        position:  new Vec3(cos($time * 2.1) * 3.0, 0.5, sin($time * 2.1) * 3.0),
        color:     new Color(1.0, 0.15, 0.1),
        intensity: 3.0,
        radius:    6.0,
    ));

    // Green — medium, mid
    $cl->add(new AddPointLight(
        position:  new Vec3(cos($time * 1.3 + 2.1) * 2.5, 1.5, sin($time * 1.3 + 2.1) * 2.5),
        color:     new Color(0.1, 1.0, 0.2),
        intensity: 3.0,
        radius:    6.0,
    ));

    // Blue — slow, high
    $cl->add(new AddPointLight(
        position:  new Vec3(cos($time * 0.7 + 4.2) * 3.5, 3.0, sin($time * 0.7 + 4.2) * 3.5),
        color:     new Color(0.15, 0.4, 1.0),
        intensity: 3.0,
        radius:    6.0,
    ));
});

$engine->run();
