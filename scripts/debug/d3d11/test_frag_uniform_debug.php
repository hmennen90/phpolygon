<?php
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 600, 'height' => 400, 'title' => 'Fragment Uniform Debug']);
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
uniform float u_intensity;

void main() {
    FragColor = vec4(u_color * u_intensity, 1.0);
}
GLSL;

$shader = vio_shader($ctx, ['vertex' => $vs, 'fragment' => $fs, 'format' => VIO_SHADER_GLSL_RAW]);
if ($shader === false) { echo "SHADER FAILED\n"; vio_destroy($ctx); exit(1); }

// Use vio_shader_reflect to see what was detected
$reflect = vio_shader_reflect($shader);
echo "\n=== Vertex shader reflection ===\n";
if (isset($reflect['vertex'])) {
    echo "  UBOs: " . count($reflect['vertex']['ubos'] ?? []) . "\n";
    foreach ($reflect['vertex']['ubos'] ?? [] as $ubo) {
        echo "    - {$ubo['name']} (set={$ubo['set']}, binding={$ubo['binding']})\n";
    }
    echo "  Uniforms: " . count($reflect['vertex']['uniforms'] ?? []) . "\n";
    foreach ($reflect['vertex']['uniforms'] ?? [] as $u) {
        echo "    - {$u['name']} (binding={$u['binding']})\n";
    }
}

echo "\n=== Fragment shader reflection ===\n";
if (isset($reflect['fragment'])) {
    echo "  UBOs: " . count($reflect['fragment']['ubos'] ?? []) . "\n";
    foreach ($reflect['fragment']['ubos'] ?? [] as $ubo) {
        echo "    - {$ubo['name']} (set={$ubo['set']}, binding={$ubo['binding']})\n";
    }
    echo "  Uniforms: " . count($reflect['fragment']['uniforms'] ?? []) . "\n";
    foreach ($reflect['fragment']['uniforms'] ?? [] as $u) {
        echo "    - {$u['name']} (binding={$u['binding']})\n";
    }
}

$pipeline = vio_pipeline($ctx, ['shader' => $shader, 'depth_test' => true, 'cull_mode' => VIO_CULL_NONE]);

$mesh = vio_mesh($ctx, [
    'vertices' => [
        -0.5,-0.5, 0.5,  0.5,-0.5, 0.5,  0.5, 0.5, 0.5,
        -0.5,-0.5, 0.5,  0.5, 0.5, 0.5, -0.5, 0.5, 0.5,
    ],
    'layout' => [VIO_FLOAT3],
    'topology' => VIO_TRIANGLES,
]);

$time = 0;
for ($i = 0; $i < 300; $i++) {
    vio_begin($ctx);
    vio_clear($ctx, 0.1, 0.15, 0.2);
    vio_bind_pipeline($ctx, $pipeline);

    $fov = 1.0;
    $proj = [$fov,0,0,0, 0,$fov*1.5,0,0, 0,0,-1.02,-1, 0,0,-0.2,0];
    $view = [1,0,0,0, 0,1,0,0, 0,0,1,0, 0,0,-3,1];

    $angle = $time * 0.02;
    $c = cos($angle); $s = sin($angle);
    $model = [$c,0,$s,0, 0,1,0,0, -$s,0,$c,0, 0,0,0,1];

    vio_set_uniform($ctx, 'u_model', $model);
    vio_set_uniform($ctx, 'u_view', $view);
    vio_set_uniform($ctx, 'u_projection', $proj);
    vio_set_uniform($ctx, 'u_color', [1.0, 0.3, 0.0]);  // orange
    vio_set_uniform($ctx, 'u_intensity', 1.0);

    vio_draw($ctx, $mesh);
    vio_end($ctx);
    vio_poll_events($ctx);
    usleep(16666);
    $time++;
}

echo "\nDone - did you see a rotating ORANGE quad?\n";
vio_destroy($ctx);
