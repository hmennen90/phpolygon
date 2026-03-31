<?php

/**
 * PHPolygon — Vulkan materials showcase
 *
 * Five spheres demonstrating the PBR material parameter space:
 *   [0] Matte plastic — low metallic, high roughness
 *   [1] Rough iron    — high metallic, high roughness
 *   [2] Brushed steel — high metallic, medium roughness
 *   [3] Polished chrome — high metallic, low roughness
 *   [4] Emissive      — glowing surface
 *
 * Run: php examples/vulkan_materials.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPolygon\Component\Camera3DComponent;
use PHPolygon\Component\DirectionalLight;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\Transform3D;
use PHPolygon\Engine;
use PHPolygon\EngineConfig;
use PHPolygon\Geometry\MeshRegistry;
use PHPolygon\Geometry\SphereMesh;
use PHPolygon\Math\Vec3;
use PHPolygon\Rendering\Color;
use PHPolygon\Rendering\Material;
use PHPolygon\Rendering\MaterialRegistry;
use PHPolygon\Rendering\Command\SetSkyColors;
use PHPolygon\Rendering\RenderCommandList;
use PHPolygon\System\Camera3DSystem;
use PHPolygon\System\Renderer3DSystem;

$engine = new Engine(new EngineConfig(
    title:           'PHPolygon — Vulkan materials',
    width:           1280,
    height:          720,
    is3D:            true,
    renderBackend3D: 'vulkan',
));

$engine->onInit(function () use ($engine): void {
    MeshRegistry::register('sphere', SphereMesh::generate(radius: 0.8, stacks: 24, slices: 32));

    $materials = [
        ['matte_plastic',   new Material(albedo: new Color(0.8, 0.2, 0.2), roughness: 0.9, metallic: 0.0)],
        ['rough_iron',      new Material(albedo: new Color(0.5, 0.5, 0.5), roughness: 0.8, metallic: 1.0)],
        ['brushed_steel',   new Material(albedo: new Color(0.7, 0.7, 0.75), roughness: 0.4, metallic: 1.0)],
        ['polished_chrome', new Material(albedo: new Color(0.9, 0.9, 0.95), roughness: 0.05, metallic: 1.0)],
        ['emissive_glow',   new Material(albedo: new Color(0.1, 0.1, 0.1), roughness: 0.5, metallic: 0.0,
                                         emission: new Color(0.2, 0.8, 1.0))],
    ];

    foreach ($materials as [$id, $mat]) {
        MaterialRegistry::register($id, $mat);
    }

    $commandList = $engine->commandList3D ?? new RenderCommandList();
    $engine->world->addSystem(new Camera3DSystem($commandList, 1280, 720));
    $engine->world->addSystem(new Renderer3DSystem($engine->renderer3D, $commandList));

    // Camera positioned to show the row of spheres
    $camera = $engine->world->createEntity();
    $camera->attach(new Camera3DComponent(fov: 55.0, active: true));
    $camera->attach(new Transform3D(position: new Vec3(0.0, 1.0, 8.0)));

    // Key light from upper-left
    $sun = $engine->world->createEntity();
    $sun->attach(new DirectionalLight(
        direction: new Vec3(-1.0, -0.6, -0.4),
        color:     new Color(1.0, 0.95, 0.9),
        intensity: 1.5,
    ));
    $sun->attach(new Transform3D());

    // Fill light from right
    $fill = $engine->world->createEntity();
    $fill->attach(new DirectionalLight(
        direction: new Vec3(1.0, -0.2, -0.3),
        color:     new Color(0.4, 0.5, 0.7),
        intensity: 0.4,
    ));
    $fill->attach(new Transform3D());

    // Place spheres in a row
    $xs = [-4.0, -2.0, 0.0, 2.0, 4.0];
    foreach ($materials as $i => [$id]) {
        $sphere = $engine->world->createEntity();
        $sphere->attach(new MeshRenderer('sphere', $id));
        $sphere->attach(new Transform3D(position: new Vec3($xs[$i], 0.0, 0.0)));
    }
});

$engine->onUpdate(function (Engine $engine, float $dt): void {
    $engine->commandList3D?->add(new SetSkyColors(
        skyColor:     new Color(0.08, 0.10, 0.15),
        horizonColor: new Color(0.25, 0.28, 0.35),
    ));
});

$engine->run();
