<?php
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 100, 'height' => 100, 'title' => 'Bindings']);

$vsPath = 'D:/PhpstormProjects/phpolygon/phpolygon/resources/shaders/source/mesh3d.vert.glsl';
$fsPath = 'D:/PhpstormProjects/phpolygon/phpolygon/resources/shaders/source/mesh3d.frag.glsl';
$shader = vio_shader($ctx, ['vertex' => file_get_contents($vsPath), 'fragment' => file_get_contents($fsPath), 'format' => VIO_SHADER_GLSL_RAW]);
if (!$shader) { echo "SHADER FAILED\n"; exit(1); }

$reflect = vio_shader_reflect($shader);

echo "=== FRAGMENT TEXTURES (samplers) ===\n";
foreach ($reflect['fragment']['textures'] ?? [] as $tex) {
    echo "  '{$tex['name']}' set={$tex['set']} binding={$tex['binding']}\n";
}

echo "\n=== FRAGMENT UBOs ===\n";
foreach ($reflect['fragment']['ubos'] ?? [] as $ubo) {
    echo "  '{$ubo['name']}' set={$ubo['set']} binding={$ubo['binding']}\n";
}

echo "\n=== VERTEX TEXTURES ===\n";
foreach ($reflect['vertex']['textures'] ?? [] as $tex) {
    echo "  '{$tex['name']}' set={$tex['set']} binding={$tex['binding']}\n";
}

vio_destroy($ctx);
