<?php
/**
 * Test PHPolygon's unlit shader on D3D11.
 * Vertex: u_model, u_view, u_projection, u_use_instancing (4 uniforms)
 * Fragment: u_albedo, u_emission, u_alpha, u_fog_color, u_fog_near, u_fog_far, u_camera_pos (7 uniforms)
 */
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 800, 'height' => 600, 'title' => 'PHPolygon Unlit Shader Test']);
echo 'Backend: ' . vio_backend_name($ctx) . PHP_EOL;

$vs = <<<'GLSL'
#version 410 core

layout(location = 0) in vec3 a_position;
layout(location = 1) in vec3 a_normal;
layout(location = 2) in vec2 a_uv;

uniform mat4 u_model;
uniform mat4 u_view;
uniform mat4 u_projection;
uniform int  u_use_instancing;

out vec3 v_worldPos;

void main() {
    mat4 model = u_model;
    vec4 worldPos = model * vec4(a_position, 1.0);
    v_worldPos = worldPos.xyz;
    gl_Position = u_projection * u_view * worldPos;
}
GLSL;

$fs = <<<'GLSL'
#version 410 core

in vec3 v_worldPos;

uniform vec3 u_albedo;
uniform vec3 u_emission;
uniform float u_alpha;
uniform vec3 u_fog_color;
uniform float u_fog_near;
uniform float u_fog_far;
uniform vec3 u_camera_pos;

out vec4 frag_color;

void main() {
    vec3 color = u_albedo + u_emission;

    // Distance fog
    float dist = length(v_worldPos - u_camera_pos);
    float fogFactor = clamp((dist - u_fog_near) / (u_fog_far - u_fog_near), 0.0, 1.0);
    color = mix(color, u_fog_color, fogFactor);

    frag_color = vec4(color, u_alpha);
}
GLSL;

$shader = vio_shader($ctx, ['vertex' => $vs, 'fragment' => $fs, 'format' => VIO_SHADER_GLSL_RAW]);
if ($shader === false) { echo "SHADER FAILED\n"; vio_destroy($ctx); exit(1); }
echo "Shader compiled OK\n";

// Reflection
$reflect = vio_shader_reflect($shader);
echo "Vertex UBOs: " . count($reflect['vertex']['ubos'] ?? []) . "\n";
echo "Fragment UBOs: " . count($reflect['fragment']['ubos'] ?? []) . "\n";

$pipeline = vio_pipeline($ctx, [
    'shader' => $shader,
    'depth_test' => true,
    'cull_mode' => VIO_CULL_NONE,
]);
if ($pipeline === false) { echo "PIPELINE FAILED\n"; vio_destroy($ctx); exit(1); }
echo "Pipeline OK\n";

// Cube mesh (pos + normal + uv)
$mesh = vio_mesh($ctx, [
    'vertices' => [
        // Front face (pos, normal, uv)
        -1,-1, 1,  0,0,1,  0,0,
         1,-1, 1,  0,0,1,  1,0,
         1, 1, 1,  0,0,1,  1,1,
        -1,-1, 1,  0,0,1,  0,0,
         1, 1, 1,  0,0,1,  1,1,
        -1, 1, 1,  0,0,1,  0,1,
        // Right face
         1,-1, 1,  1,0,0,  0,0,
         1,-1,-1,  1,0,0,  1,0,
         1, 1,-1,  1,0,0,  1,1,
         1,-1, 1,  1,0,0,  0,0,
         1, 1,-1,  1,0,0,  1,1,
         1, 1, 1,  1,0,0,  0,1,
        // Back face
         1,-1,-1,  0,0,-1, 0,0,
        -1,-1,-1,  0,0,-1, 1,0,
        -1, 1,-1,  0,0,-1, 1,1,
         1,-1,-1,  0,0,-1, 0,0,
        -1, 1,-1,  0,0,-1, 1,1,
         1, 1,-1,  0,0,-1, 0,1,
        // Left face
        -1,-1,-1, -1,0,0,  0,0,
        -1,-1, 1, -1,0,0,  1,0,
        -1, 1, 1, -1,0,0,  1,1,
        -1,-1,-1, -1,0,0,  0,0,
        -1, 1, 1, -1,0,0,  1,1,
        -1, 1,-1, -1,0,0,  0,1,
        // Top face
        -1, 1, 1,  0,1,0,  0,0,
         1, 1, 1,  0,1,0,  1,0,
         1, 1,-1,  0,1,0,  1,1,
        -1, 1, 1,  0,1,0,  0,0,
         1, 1,-1,  0,1,0,  1,1,
        -1, 1,-1,  0,1,0,  0,1,
        // Bottom face
        -1,-1,-1,  0,-1,0, 0,0,
         1,-1,-1,  0,-1,0, 1,0,
         1,-1, 1,  0,-1,0, 1,1,
        -1,-1,-1,  0,-1,0, 0,0,
         1,-1, 1,  0,-1,0, 1,1,
        -1,-1, 1,  0,-1,0, 0,1,
    ],
    'layout' => [VIO_FLOAT3, VIO_FLOAT3, VIO_FLOAT2],
    'topology' => VIO_TRIANGLES,
]);

$time = 0;
for ($i = 0; $i < 400; $i++) {
    vio_begin($ctx);
    vio_clear($ctx, 0.4, 0.6, 0.8);  // light blue sky
    vio_bind_pipeline($ctx, $pipeline);

    // Perspective projection
    $fov = 1.0;
    $aspect = 800.0 / 600.0;
    $proj = [$fov/$aspect,0,0,0, 0,$fov,0,0, 0,0,-1.002,-1, 0,0,-0.2002,0];

    // View - camera at (0, 2, 5) looking at origin
    $camPos = [0, 2, 5];
    $view = [1,0,0,0, 0,0.928,-0.371,0, 0,0.371,0.928,0, 0,-2.785,-4.999,1];

    // Rotating model
    $angle = $time * 0.015;
    $c = cos($angle); $s = sin($angle);
    $model = [$c,0,$s,0, 0,1,0,0, -$s,0,$c,0, 0,0,0,1];

    // Vertex uniforms
    vio_set_uniform($ctx, 'u_model', $model);
    vio_set_uniform($ctx, 'u_view', $view);
    vio_set_uniform($ctx, 'u_projection', $proj);
    vio_set_uniform($ctx, 'u_use_instancing', 0);

    // Fragment uniforms
    vio_set_uniform($ctx, 'u_albedo', [0.2, 0.7, 0.3]);     // green
    vio_set_uniform($ctx, 'u_emission', [0.0, 0.0, 0.0]);
    vio_set_uniform($ctx, 'u_alpha', 1.0);
    vio_set_uniform($ctx, 'u_fog_color', [0.4, 0.6, 0.8]);
    vio_set_uniform($ctx, 'u_fog_near', 10.0);
    vio_set_uniform($ctx, 'u_fog_far', 50.0);
    vio_set_uniform($ctx, 'u_camera_pos', $camPos);

    vio_draw($ctx, $mesh);
    vio_end($ctx);
    vio_poll_events($ctx);
    usleep(16666);
    $time++;
}

echo "Done - did you see a rotating GREEN cube?\n";
vio_destroy($ctx);
