<?php
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 600, 'height' => 400, 'title' => 'D3D11 Uniform Test']);
echo 'Backend: ' . vio_backend_name($ctx) . PHP_EOL;

$vs = <<<'GLSL'
#version 410 core
layout(location=0) in vec3 aPos;

uniform mat4 u_model;
uniform mat4 u_view;
uniform mat4 u_projection;

void main() {
    gl_Position = u_projection * u_view * u_model * vec4(aPos, 1.0);
}
GLSL;

$fs = <<<'GLSL'
#version 410 core
layout(location=0) out vec4 FragColor;

uniform vec3 u_color;

void main() {
    FragColor = vec4(u_color, 1.0);
}
GLSL;

$shader = vio_shader($ctx, ['vertex' => $vs, 'fragment' => $fs, 'format' => VIO_SHADER_GLSL_RAW]);
if ($shader === false) { echo "SHADER FAILED\n"; vio_destroy($ctx); exit(1); }
echo "Shader OK (uniforms: u_model, u_view, u_projection, u_color)\n";

$pipeline = vio_pipeline($ctx, ['shader' => $shader, 'depth_test' => true, 'cull_mode' => VIO_CULL_NONE]);
if ($pipeline === false) { echo "PIPELINE FAILED\n"; vio_destroy($ctx); exit(1); }
echo "Pipeline OK\n";

// Simple cube vertices (just front face for now)
$mesh = vio_mesh($ctx, [
    'vertices' => [
        -0.5,-0.5, 0.5,  0.5,-0.5, 0.5,  0.5, 0.5, 0.5,
        -0.5,-0.5, 0.5,  0.5, 0.5, 0.5, -0.5, 0.5, 0.5,
    ],
    'layout' => [VIO_FLOAT3],
    'topology' => VIO_TRIANGLES,
]);

// Identity matrices
$identity = [1,0,0,0, 0,1,0,0, 0,0,1,0, 0,0,0,1];

$time = 0;
for ($i = 0; $i < 300; $i++) {
    vio_begin($ctx);
    vio_clear($ctx, 0.1, 0.15, 0.2);
    vio_bind_pipeline($ctx, $pipeline);

    // Simple perspective-ish projection
    $fov = 1.0;
    $proj = [$fov,0,0,0, 0,$fov*1.5,0,0, 0,0,-1.02,-1, 0,0,-0.2,0];

    // Simple view (camera at z=3)
    $view = [1,0,0,0, 0,1,0,0, 0,0,1,0, 0,0,-3,1];

    // Rotating model
    $angle = $time * 0.02;
    $c = cos($angle); $s = sin($angle);
    $model = [$c,0,$s,0, 0,1,0,0, -$s,0,$c,0, 0,0,0,1];

    vio_set_uniform($ctx, 'u_model', $model);
    vio_set_uniform($ctx, 'u_view', $view);
    vio_set_uniform($ctx, 'u_projection', $proj);
    vio_set_uniform($ctx, 'u_color', [0.2, 0.8, 0.3]);

    vio_draw($ctx, $mesh);
    vio_end($ctx);
    vio_poll_events($ctx);
    usleep(16666);
    $time++;
}

echo "Done - did you see a rotating green quad?\n";
vio_destroy($ctx);
