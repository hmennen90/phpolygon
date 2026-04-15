<?php
/**
 * Test sampler2DShadow (comparison sampler) on D3D11.
 * Same as NDC test but using the comparison sampler like PHPolygon does.
 */
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 800, 'height' => 600, 'title' => 'Shadow Comparison']);

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

// Main pass: use sampler2DShadow like PHPolygon
$main_vs = <<<'GLSL'
#version 410 core
layout(location = 0) in vec3 a_position;
out vec2 v_uv;
void main() {
    gl_Position = vec4(a_position, 1.0);
    v_uv = a_position.xy * 0.5 + 0.5;
}
GLSL;

// Test A: sampler2D (known working)
$main_fs_2d = <<<'GLSL'
#version 410 core
in vec2 v_uv;
uniform sampler2D u_shadow_map;
out vec4 frag_color;
void main() {
    float depth = texture(u_shadow_map, v_uv).r;
    float shadow = (depth < 0.99) ? 0.3 : 1.0;
    frag_color = vec4(vec3(0.9, 0.9, 0.8) * shadow, 1.0);
}
GLSL;

// Test B: sampler2DShadow (comparison)
$main_fs_cmp = <<<'GLSL'
#version 410 core
in vec2 v_uv;
uniform sampler2DShadow u_shadow_map;
out vec4 frag_color;
void main() {
    // texture() on sampler2DShadow: .xy = UV, .z = reference depth
    // Returns 1.0 if reference <= depth in map, 0.0 otherwise
    float shadow = texture(u_shadow_map, vec3(v_uv, 0.5));
    frag_color = vec4(vec3(0.9, 0.9, 0.8) * (0.3 + shadow * 0.7), 1.0);
}
GLSL;

$shadow_shader = vio_shader($ctx, ['vertex' => $shadow_vs, 'fragment' => $shadow_fs, 'format' => VIO_SHADER_GLSL_RAW]);

// Try sampler2DShadow version
$main_shader = vio_shader($ctx, ['vertex' => $main_vs, 'fragment' => $main_fs_cmp, 'format' => VIO_SHADER_GLSL_RAW]);
if (!$main_shader) {
    echo "sampler2DShadow shader FAILED - falling back to sampler2D\n";
    $main_shader = vio_shader($ctx, ['vertex' => $main_vs, 'fragment' => $main_fs_2d, 'format' => VIO_SHADER_GLSL_RAW]);
} else {
    echo "sampler2DShadow shader OK\n";
}

$shadow_pipe = vio_pipeline($ctx, ['shader' => $shadow_shader, 'depth_test' => true, 'cull_mode' => VIO_CULL_NONE]);
$main_pipe = vio_pipeline($ctx, ['shader' => $main_shader, 'depth_test' => false, 'cull_mode' => VIO_CULL_NONE]);

$shadow_rt = vio_render_target($ctx, ['width' => 512, 'height' => 512, 'depth_only' => true]);

$occluder = vio_mesh($ctx, [
    'vertices' => [-0.3,-0.3,0.0, 0.3,-0.3,0.0, 0.0,0.3,0.0],
    'layout' => [VIO_FLOAT3],
    'topology' => VIO_TRIANGLES,
]);
$quad = vio_mesh($ctx, [
    'vertices' => [-1,-1,0, 1,-1,0, 1,1,0, -1,-1,0, 1,1,0, -1,1,0],
    'layout' => [VIO_FLOAT3],
    'topology' => VIO_TRIANGLES,
]);

echo "Running...\n";

for ($i = 0; $i < 300; $i++) {
    vio_begin($ctx);

    vio_bind_render_target($ctx, $shadow_rt);
    vio_viewport($ctx, 0, 0, 512, 512);
    vio_clear($ctx, 1, 1, 1, 1);
    vio_bind_pipeline($ctx, $shadow_pipe);
    vio_draw($ctx, $occluder);
    vio_unbind_render_target($ctx);

    vio_viewport($ctx, 0, 0, 800, 600);
    vio_clear($ctx, 0.1, 0.1, 0.1);
    vio_bind_pipeline($ctx, $main_pipe);
    $shadowTex = vio_render_target_texture($shadow_rt);
    vio_bind_texture($ctx, $shadowTex, 0);
    vio_set_uniform($ctx, 'u_shadow_map', 0);
    vio_draw($ctx, $quad);

    vio_end($ctx);
    vio_poll_events($ctx);
    usleep(16666);
}

echo "Done - should show triangle shadow (dark area) using comparison sampler\n";
vio_destroy($ctx);
