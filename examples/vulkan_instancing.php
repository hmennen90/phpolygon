<?php

/**
 * PHPolygon — Vulkan instancing showcase
 *
 * Renders a 10×10 grid of 100 boxes via DrawMeshInstanced (single GPU draw call).
 * isStatic: true — the instance buffer is uploaded once and reused every frame.
 *
 * Run: php examples/vulkan_instancing.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPolygon\Component\Camera3DComponent;
use PHPolygon\Component\DirectionalLight;
use PHPolygon\Component\Transform3D;
use PHPolygon\Engine;
use PHPolygon\EngineConfig;
use PHPolygon\Geometry\BoxMesh;
use PHPolygon\Geometry\MeshRegistry;
use PHPolygon\Math\Mat4;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Rendering\Color;
use PHPolygon\Rendering\Material;
use PHPolygon\Rendering\MaterialRegistry;
use PHPolygon\Rendering\Command\DrawMeshInstanced;
use PHPolygon\Rendering\Command\SetSkyColors;
use PHPolygon\Rendering\RenderCommandList;
use PHPolygon\System\Camera3DSystem;
use PHPolygon\System\Renderer3DSystem;

$engine = new Engine(new EngineConfig(
    title:           'PHPolygon — Vulkan instancing (100 boxes)',
    width:           1280,
    height:          720,
    is3D:            true,
    renderBackend3D: 'vulkan',
));

/** @var Mat4[] $instanceMatrices */
$instanceMatrices = [];

$engine->onInit(function () use ($engine, &$instanceMatrices): void {
    MeshRegistry::register('box', BoxMesh::generate(1.0, 1.0, 1.0));

    MaterialRegistry::register('stone', new Material(
        albedo:    new Color(0.55, 0.52, 0.48),
        roughness: 0.75,
        metallic:  0.0,
    ));

    $commandList = $engine->commandList3D ?? new RenderCommandList();
    $engine->world->addSystem(new Camera3DSystem($commandList, 1280, 720));
    $engine->world->addSystem(new Renderer3DSystem($engine->renderer3D, $commandList));

    // Camera looking down at the grid
    $camera = $engine->world->createEntity();
    $camera->attach(new Camera3DComponent(fov: 60.0, active: true));
    $camera->attach(new Transform3D(position: new Vec3(0.0, 18.0, 22.0)));

    $sun = $engine->world->createEntity();
    $sun->attach(new DirectionalLight(
        direction: new Vec3(-0.6, -1.0, -0.4),
        color:     new Color(1.0, 0.95, 0.85),
        intensity: 1.2,
    ));
    $sun->attach(new Transform3D());

    // Build 10×10 instance matrices (spacing = 2.5 units)
    $cols = 10;
    $rows = 10;
    $spacing = 2.5;
    $offsetX = -($cols - 1) * $spacing * 0.5;
    $offsetZ = -($rows - 1) * $spacing * 0.5;

    for ($row = 0; $row < $rows; $row++) {
        for ($col = 0; $col < $cols; $col++) {
            $instanceMatrices[] = Mat4::trs(
                new Vec3($offsetX + $col * $spacing, 0.0, $offsetZ + $row * $spacing),
                Quaternion::identity(),
                Vec3::one(),
            );
        }
    }
});

$engine->onUpdate(function (Engine $engine, float $dt) use (&$instanceMatrices): void {
    $cl = $engine->commandList3D;
    if ($cl === null) {
        return;
    }

    $cl->add(new SetSkyColors(
        skyColor:     new Color(0.45, 0.60, 0.80),
        horizonColor: new Color(0.75, 0.82, 0.90),
    ));

    // One draw call for all 100 boxes; isStatic skips re-upload after first frame
    $cl->add(new DrawMeshInstanced(
        meshId:     'box',
        materialId: 'stone',
        matrices:   $instanceMatrices,
        isStatic:   true,
    ));
});

$engine->run();
