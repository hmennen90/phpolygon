<?php
/**
 * Shadow map test in NDC — no matrix math, just raw clip-space geometry.
 * Proves the shadow map pipeline works on D3D11.
 */
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 800, 'height' => 600, 'title' => 'Shadow NDC']);

// Shadow pass: just write depth
$shadow_vs = <<<'GLSL'
#version 410 core
layout(location = 0) in vec3 a_position;
void main() {
    gl_Position = vec4(a_position, 1.0);
}
GLSL;
$shadow_fs = <<<'GLSL'
#version 410 core
void main() {}
GLSL;

// Main pass: sample shadow map as regular texture
$main_vs = <<<'GLSL'
#version 410 core
layout(location = 0) in vec3 a_position;
out vec2 v_uv;
void main() {
    gl_Position = vec4(a_position, 1.0);
    v_uv = a_position.xy * 0.5 + 0.5;
}
GLSL;
$main_fs = <<<'GLSL'
#version 410 core
in vec2 v_uv;
uniform sampler2D u_shadow_map;
uniform vec3 u_color;
out vec4 frag_color;
void main() {
    float depth = texture(u_shadow_map, v_uv).r;
    // depth < 1.0 means something was drawn in shadow pass at this UV
    float shadow = (depth < 0.99) ? 0.3 : 1.0;
    frag_color = vec4(u_color * shadow, 1.0);
}
GLSL;

$shadow_shader = vio_shader($ctx, ['vertex' => $shadow_vs, 'fragment' => $shadow_fs, 'format' => VIO_SHADER_GLSL_RAW]);
$main_shader = vio_shader($ctx, ['vertex' => $main_vs, 'fragment' => $main_fs, 'format' => VIO_SHADER_GLSL_RAW]);
if (!$shadow_shader || !$main_shader) { echo "SHADER FAILED\n"; exit(1); }
echo "Shaders OK\n";

$shadow_pipe = vio_pipeline($ctx, ['shader' => $shadow_shader, 'depth_test' => true, 'cull_mode' => VIO_CULL_NONE]);
$main_pipe = vio_pipeline($ctx, ['shader' => $main_shader, 'depth_test' => false, 'cull_mode' => VIO_CULL_NONE]);

$shadow_rt = vio_render_target($ctx, ['width' => 512, 'height' => 512, 'depth_only' => true]);
if (!$shadow_rt) { echo "RT FAILED\n"; exit(1); }
echo "RT OK\n";

// Small triangle in clip space (z=0.5 -> depth=0.75 after perspective divide)
$occluder = vio_mesh($ctx, [
    'vertices' => [
        -0.3, -0.3, 0.0,
         0.3, -0.3, 0.0,
         0.0,  0.3, 0.0,
    ],
    'layout' => [VIO_FLOAT3],
    'topology' => VIO_TRIANGLES,
]);

// Full-screen quad for main pass
$quad = vio_mesh($ctx, [
    'vertices' => [
        -1,-1, 0,  1,-1, 0,  1, 1, 0,
        -1,-1, 0,  1, 1, 0, -1, 1, 0,
    ],
    'layout' => [VIO_FLOAT3],
    'topology' => VIO_TRIANGLES,
]);
echo "Meshes OK\n";

for ($i = 0; $i < 300; $i++) {
    vio_begin($ctx);

    // SHADOW PASS: draw triangle into depth buffer
    vio_bind_render_target($ctx, $shadow_rt);
    vio_viewport($ctx, 0, 0, 512, 512);
    vio_clear($ctx, 1, 1, 1, 1);
    vio_bind_pipeline($ctx, $shadow_pipe);
    vio_draw($ctx, $occluder);
    vio_unbind_render_target($ctx);

    // MAIN PASS: draw full-screen quad, visualize shadow map
    vio_viewport($ctx, 0, 0, 800, 600);
    vio_clear($ctx, 0.1, 0.1, 0.1);
    vio_bind_pipeline($ctx, $main_pipe);

    $shadowTex = vio_render_target_texture($shadow_rt);
    vio_bind_texture($ctx, $shadowTex, 0);
    vio_set_uniform($ctx, 'u_shadow_map', 0);
    vio_set_uniform($ctx, 'u_color', [0.9, 0.9, 0.8]);
    vio_draw($ctx, $quad);

    vio_end($ctx);
    vio_poll_events($ctx);
    usleep(16666);
}

echo "Done - should see a bright quad with a dark triangle shape in the center\n";
vio_destroy($ctx);
