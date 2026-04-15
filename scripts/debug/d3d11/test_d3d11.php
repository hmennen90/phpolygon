<?php
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 400, 'height' => 300, 'title' => 'D3D11 Triangle Test']);
echo 'Backend: ' . vio_backend_name($ctx) . PHP_EOL;

$vs = <<<'GLSL'
#version 410 core
layout(location=0) in vec3 aPos;
void main() {
    gl_Position = vec4(aPos, 1.0);
}
GLSL;

$fs = <<<'GLSL'
#version 410 core
layout(location=0) out vec4 FragColor;
void main() {
    FragColor = vec4(1.0, 0.0, 0.0, 1.0);
}
GLSL;

$shader = vio_shader($ctx, ['vertex' => $vs, 'fragment' => $fs, 'format' => VIO_SHADER_GLSL_RAW]);
if ($shader === false) {
    echo "SHADER FAILED\n";
    vio_destroy($ctx);
    exit(1);
}
echo "Shader OK\n";

$pipeline = vio_pipeline($ctx, ['shader' => $shader, 'depth_test' => false]);
if ($pipeline === false) {
    echo "PIPELINE FAILED\n";
    vio_destroy($ctx);
    exit(1);
}
echo "Pipeline OK\n";

$mesh = vio_mesh($ctx, [
    'vertices' => [-0.5,-0.5,0.0, 0.5,-0.5,0.0, 0.0,0.5,0.0],
    'layout' => [VIO_FLOAT3],
    'topology' => VIO_TRIANGLES,
]);
echo "Mesh OK\n";

for ($i = 0; $i < 180; $i++) {
    vio_begin($ctx);
    vio_clear($ctx, 0.1, 0.1, 0.3);
    vio_bind_pipeline($ctx, $pipeline);
    vio_draw($ctx, $mesh);
    vio_end($ctx);
    vio_poll_events($ctx);
    usleep(16666);
}

echo "Done - did you see a red triangle on blue background?\n";
vio_destroy($ctx);
