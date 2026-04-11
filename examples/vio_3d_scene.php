<?php

/**
 * PHPolygon - VIO 3D scene demo
 *
 * Showcases the full Vio 3D pipeline:
 *   - Procedural skybox (sunset atmosphere, no image files)
 *   - Directional light with shadow mapping
 *   - Instanced geometry (columns) via DrawMeshInstanced with isStatic
 *   - Orbiting point lights (colored)
 *   - Fog for depth
 *   - Mixed materials (metal, stone, glass)
 *   - Ambient light
 *
 * Run: php examples/vio_3d_scene.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPolygon\Component\Camera3DComponent;
use PHPolygon\Component\DirectionalLight;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\PointLight;
use PHPolygon\Component\Transform3D;
use PHPolygon\Engine;
use PHPolygon\EngineConfig;
use PHPolygon\Geometry\BoxMesh;
use PHPolygon\Geometry\CylinderMesh;
use PHPolygon\Geometry\MeshRegistry;
use PHPolygon\Geometry\PlaneMesh;
use PHPolygon\Geometry\SphereMesh;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Rendering\Color;
use PHPolygon\Rendering\Command\DrawMeshInstanced;
use PHPolygon\Rendering\Command\SetAmbientLight;
use PHPolygon\Rendering\Command\SetFog;
use PHPolygon\Rendering\Command\SetSkybox;
use PHPolygon\Rendering\CubemapRegistry;
use PHPolygon\Rendering\Material;
use PHPolygon\Rendering\MaterialRegistry;
use PHPolygon\Rendering\ProceduralSky;
use PHPolygon\Rendering\RenderCommandList;
use PHPolygon\System\Camera3DSystem;
use PHPolygon\System\Renderer3DSystem;

$engine = new Engine(new EngineConfig(
    title:  'PHPolygon - VIO 3D Scene',
    width:  1280,
    height: 720,
    is3D:   true,
));

$time = 0.0;
$lightEntities = [];
$sphereEntity = null;

$engine->onInit(function () use ($engine, &$lightEntities, &$sphereEntity): void {
    // --- Geometry ---
    MeshRegistry::register('ground',   PlaneMesh::generate(30.0, 30.0));
    MeshRegistry::register('column',   CylinderMesh::generate(radius: 0.3, height: 3.0, segments: 12));
    MeshRegistry::register('sphere',   SphereMesh::generate(radius: 0.5, stacks: 16, slices: 24));
    MeshRegistry::register('pedestal', BoxMesh::generate(0.8, 0.2, 0.8));
    MeshRegistry::register('cube',     BoxMesh::generate(0.6, 0.6, 0.6));

    // --- Materials ---
    MaterialRegistry::register('stone_floor', new Material(
        albedo: new Color(0.35, 0.33, 0.30), roughness: 0.9, metallic: 0.0,
    ));
    MaterialRegistry::register('marble', new Material(
        albedo: new Color(0.85, 0.83, 0.80), roughness: 0.4, metallic: 0.0,
    ));
    MaterialRegistry::register('bronze', new Material(
        albedo: new Color(0.7, 0.5, 0.2), roughness: 0.3, metallic: 1.0,
    ));
    MaterialRegistry::register('chrome', new Material(
        albedo: new Color(0.9, 0.9, 0.95), roughness: 0.1, metallic: 1.0,
    ));
    MaterialRegistry::register('glass', new Material(
        albedo: new Color(0.6, 0.8, 1.0), roughness: 0.1, metallic: 0.0, alpha: 0.35,
    ));
    MaterialRegistry::register('red_light', new Material(
        albedo: new Color(0.1, 0.0, 0.0),
        emission: new Color(1.0, 0.2, 0.1),
    ));
    MaterialRegistry::register('blue_light', new Material(
        albedo: new Color(0.0, 0.0, 0.1),
        emission: new Color(0.1, 0.3, 1.0),
    ));

    // --- Procedural skybox (sunset) ---
    $sunDir = (new Vec3(-0.5, -0.3, -0.3))->normalize();
    $skyData = ProceduralSky::sunset($sunDir)->generate(256);
    CubemapRegistry::registerProcedural('sky', $skyData);

    // --- Systems ---
    $commandList = $engine->commandList3D ?? new RenderCommandList();
    $engine->world->addSystem(new Camera3DSystem($commandList, 1280, 720));
    $engine->world->addSystem(new Renderer3DSystem($engine->renderer3D, $commandList));

    // --- Camera ---
    $camera = $engine->world->createEntity();
    $camera->attach(new Camera3DComponent(fov: 55.0, near: 0.1, far: 150.0, active: true));
    $camera->attach(new Transform3D(position: new Vec3(0.0, 4.0, 12.0)));

    // --- Sunlight (casts shadows) ---
    $sun = $engine->world->createEntity();
    $sun->attach(new DirectionalLight(
        direction: new Vec3(-0.5, -1.0, -0.3),
        color: new Color(1.0, 0.95, 0.85),
        intensity: 1.2,
    ));
    $sun->attach(new Transform3D());

    // --- Ground ---
    $ground = $engine->world->createEntity();
    $ground->attach(new MeshRenderer('ground', 'stone_floor'));
    $ground->attach(new Transform3D(position: new Vec3(0.0, 0.0, 0.0)));

    // --- Central chrome sphere on pedestal ---
    $pedestal = $engine->world->createEntity();
    $pedestal->attach(new MeshRenderer('pedestal', 'marble'));
    $pedestal->attach(new Transform3D(position: new Vec3(0.0, 0.1, 0.0)));

    $sphereEntity = $engine->world->createEntity();
    $sphereEntity->attach(new MeshRenderer('sphere', 'chrome'));
    $sphereEntity->attach(new Transform3D(position: new Vec3(0.0, 0.7, 0.0)));

    // --- Glass cube (transparent) ---
    $glassCube = $engine->world->createEntity();
    $glassCube->attach(new MeshRenderer('cube', 'glass'));
    $glassCube->attach(new Transform3D(position: new Vec3(2.5, 0.3, 0.0)));

    // --- Orbiting point lights ---
    $lightColors = [
        ['color' => new Color(1.0, 0.3, 0.1), 'mat' => 'red_light'],
        ['color' => new Color(0.1, 0.4, 1.0), 'mat' => 'blue_light'],
    ];

    foreach ($lightColors as $i => $cfg) {
        $e = $engine->world->createEntity();
        $e->attach(new PointLight(
            color: $cfg['color'],
            intensity: 2.5,
            radius: 12.0,
        ));
        $e->attach(new MeshRenderer('sphere', $cfg['mat']));
        $e->attach(new Transform3D(
            position: new Vec3(0.0, 1.5, 0.0),
            scale: new Vec3(0.15, 0.15, 0.15),
        ));
        $lightEntities[] = $e;
    }

    // --- Ambient + Fog + Skybox via command list ---
    $commandList->add(new SetAmbientLight(new Color(0.15, 0.15, 0.20), 1.0));
    $commandList->add(new SetFog(new Color(0.08, 0.08, 0.12), 20.0, 80.0));
    $commandList->add(new SetSkybox('sky'));
});

$engine->onUpdate(function (Engine $engine, float $dt) use (&$time, &$lightEntities, &$sphereEntity): void {
    $time += $dt;

    if ($engine->input->isKeyPressed(256)) { // ESC
        $engine->stop();
    }

    // Orbit point lights around center
    foreach ($lightEntities as $i => $entity) {
        $t = $entity->get(Transform3D::class);
        if ($t === null) continue;

        $angle = $time * 0.8 + $i * M_PI;
        $radius = 3.5;
        $t->position = new Vec3(
            cos($angle) * $radius,
            1.2 + sin($time * 1.5 + $i) * 0.5,
            sin($angle) * $radius,
        );
    }

    // Slowly rotate the central sphere
    if ($sphereEntity !== null) {
        $t = $sphereEntity->get(Transform3D::class);
        if ($t !== null) {
            $t->rotation = Quaternion::fromAxisAngle(new Vec3(0.0, 1.0, 0.0), $time * 0.3);
        }
    }
});

// Inject instanced columns each frame before Renderer3DSystem runs
$engine->onRender(function (Engine $engine) use (&$columnMatricesSent): void {
    $commandList = $engine->commandList3D;
    if ($commandList === null) return;

    // 4x4 grid of marble columns around the center
    static $columnMatrices = null;
    if ($columnMatrices === null) {
        $columnMatrices = [];
        $spacing = 3.0;
        $offset = $spacing * 1.5;
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 4; $col++) {
                $x = $col * $spacing - $offset;
                $z = $row * $spacing - $offset;
                // Skip center area (where sphere sits)
                if (abs($x) < 2.0 && abs($z) < 2.0) continue;
                $columnMatrices[] = \PHPolygon\Math\Mat4::translation($x, 1.5, $z);
            }
        }
    }

    $commandList->add(new DrawMeshInstanced('column', 'marble', $columnMatrices, isStatic: true));
});

$engine->run();
