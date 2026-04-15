<?php
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 100, 'height' => 100, 'title' => 'Reflect']);

// Load the ACTUAL mesh3d shaders from PHPolygon
$vsPath = 'D:/PhpstormProjects/phpolygon/phpolygon/resources/shaders/source/mesh3d.vert.glsl';
$fsPath = 'D:/PhpstormProjects/phpolygon/phpolygon/resources/shaders/source/mesh3d.frag.glsl';

$vs = file_get_contents($vsPath);
$fs = file_get_contents($fsPath);

$shader = vio_shader($ctx, ['vertex' => $vs, 'fragment' => $fs, 'format' => VIO_SHADER_GLSL_RAW]);
if ($shader === false) { echo "SHADER FAILED\n"; vio_destroy($ctx); exit(1); }
echo "Shader compiled OK\n";

// Test if key uniforms are findable by setting them
$pipeline = vio_pipeline($ctx, ['shader' => $shader, 'depth_test' => true, 'cull_mode' => VIO_CULL_NONE]);
vio_begin($ctx);
vio_clear($ctx, 0, 0, 0);
vio_bind_pipeline($ctx, $pipeline);

// Test setting key fragment uniforms
$testUniforms = [
    'u_albedo' => [1.0, 0.0, 0.0],
    'u_has_shadow_map' => 1,
    'u_light_space_matrix' => [1,0,0,0, 0,1,0,0, 0,0,1,0, 0,0,0,1],
    'u_dir_lights[0].direction' => [0, -1, 0],
    'u_dir_lights[0].color' => [1, 1, 1],
    'u_dir_lights[0].intensity' => 1.0,
    'u_has_environment_map' => 0,
    'u_has_cloud_shadow' => 0,
    'u_proc_mode' => 0,
];

foreach ($testUniforms as $name => $value) {
    vio_set_uniform($ctx, $name, $value);
    echo "  set '$name' - OK\n";
}

vio_end($ctx);
echo "\nAll uniforms set without error.\n";
vio_destroy($ctx);
