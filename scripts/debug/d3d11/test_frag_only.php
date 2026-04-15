<?php
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 600, 'height' => 400, 'title' => 'Fragment Uniform ONLY']);
echo 'Backend: ' . vio_backend_name($ctx) . PHP_EOL;

// Vertex shader: NO uniforms — just pass-through
$vs = <<<'GLSL'
#version 410 core
layout(location=0) in vec3 aPos;

void main() {
    gl_Position = vec4(aPos, 1.0);
}
GLSL;

// Fragment shader: color entirely from uniform
$fs = <<<'GLSL'
#version 410 core
layout(location=0) out vec4 FragColor;

uniform vec4 u_color;

void main() {
    FragColor = u_color;
}
GLSL;

$shader = vio_shader($ctx, ['vertex' => $vs, 'fragment' => $fs, 'format' => VIO_SHADER_GLSL_RAW]);
if ($shader === false) { echo "SHADER FAILED\n"; vio_destroy($ctx); exit(1); }
echo "Shader OK\n";

$pipeline = vio_pipeline($ctx, ['shader' => $shader, 'depth_test' => false, 'cull_mode' => VIO_CULL_NONE]);

// Full-screen quad in clip space
$mesh = vio_mesh($ctx, [
    'vertices' => [
        -0.8,-0.8, 0.0,  0.8,-0.8, 0.0,  0.8, 0.8, 0.0,
        -0.8,-0.8, 0.0,  0.8, 0.8, 0.0, -0.8, 0.8, 0.0,
    ],
    'layout' => [VIO_FLOAT3],
    'topology' => VIO_TRIANGLES,
]);

$time = 0;
for ($i = 0; $i < 300; $i++) {
    vio_begin($ctx);
    vio_clear($ctx, 0.1, 0.1, 0.1);  // dark gray background
    vio_bind_pipeline($ctx, $pipeline);

    // Cycle through colors
    $r = (sin($time * 0.03) + 1.0) / 2.0;
    $g = (sin($time * 0.03 + 2.094) + 1.0) / 2.0;
    $b = (sin($time * 0.03 + 4.189) + 1.0) / 2.0;
    vio_set_uniform($ctx, 'u_color', [$r, $g, $b, 1.0]);

    vio_draw($ctx, $mesh);
    vio_end($ctx);
    vio_poll_events($ctx);
    usleep(16666);
    $time++;
}

echo "Did you see a color-cycling quad? (proves fragment uniforms work)\n";
vio_destroy($ctx);
