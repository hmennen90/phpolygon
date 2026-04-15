<?php
/**
 * Test dynamic shadow map on D3D11.
 * A cube casts a shadow on a floor plane.
 * The light direction rotates over time - shadow should move.
 */
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 800, 'height' => 600, 'title' => 'Dynamic Shadow Test']);

// Shadow shader (vertex only - depth pass)
$shadow_vs = <<<'GLSL'
#version 410 core
layout(location = 0) in vec3 a_position;
uniform mat4 u_model;
uniform mat4 u_view;
uniform mat4 u_projection;
void main() {
    gl_Position = u_projection * u_view * u_model * vec4(a_position, 1.0);
}
GLSL;

$shadow_fs = <<<'GLSL'
#version 410 core
void main() {}
GLSL;

// Main shader with shadow sampling
$main_vs = <<<'GLSL'
#version 410 core
layout(location = 0) in vec3 a_position;
layout(location = 1) in vec3 a_normal;
uniform mat4 u_model;
uniform mat4 u_view;
uniform mat4 u_projection;
out vec3 v_worldPos;
out vec3 v_normal;
void main() {
    vec4 wp = u_model * vec4(a_position, 1.0);
    v_worldPos = wp.xyz;
    v_normal = mat3(u_model) * a_normal;
    gl_Position = u_projection * u_view * wp;
}
GLSL;

$main_fs = <<<'GLSL'
#version 410 core
in vec3 v_worldPos;
in vec3 v_normal;

uniform vec3 u_albedo;
uniform vec3 u_light_dir;
uniform sampler2DShadow u_shadow_map;
uniform mat4 u_light_space_matrix;
uniform int u_has_shadow_map;

out vec4 frag_color;

void main() {
    vec3 N = normalize(v_normal);
    float diff = max(dot(N, -u_light_dir), 0.0);

    float shadow = 1.0;
    if (u_has_shadow_map == 1) {
        vec4 lsPos = u_light_space_matrix * vec4(v_worldPos, 1.0);
        vec3 projCoords = lsPos.xyz / lsPos.w;
        projCoords = projCoords * 0.5 + 0.5;
        if (projCoords.z <= 1.0) {
            shadow = texture(u_shadow_map, vec3(projCoords.xy, projCoords.z - 0.005));
        }
    }

    vec3 color = u_albedo * (0.15 + diff * shadow * 0.85);
    frag_color = vec4(color, 1.0);
}
GLSL;

$shadow_shader = vio_shader($ctx, ['vertex' => $shadow_vs, 'fragment' => $shadow_fs, 'format' => VIO_SHADER_GLSL_RAW]);
$main_shader = vio_shader($ctx, ['vertex' => $main_vs, 'fragment' => $main_fs, 'format' => VIO_SHADER_GLSL_RAW]);
if (!$shadow_shader || !$main_shader) { echo "SHADER FAILED\n"; exit(1); }

$shadow_pipeline = vio_pipeline($ctx, ['shader' => $shadow_shader, 'depth_test' => true, 'cull_mode' => VIO_CULL_BACK]);
$main_pipeline = vio_pipeline($ctx, ['shader' => $main_shader, 'depth_test' => true, 'cull_mode' => VIO_CULL_NONE]);

// Shadow render target
$shadow_rt = vio_render_target($ctx, ['width' => 1024, 'height' => 1024, 'depth_only' => true]);
if (!$shadow_rt) { echo "SHADOW RT FAILED\n"; exit(1); }

// Cube mesh (pos + normal)
$cube = vio_mesh($ctx, [
    'vertices' => [
        // Front
        -0.5,-0.5,0.5, 0,0,1,  0.5,-0.5,0.5, 0,0,1,  0.5,0.5,0.5, 0,0,1,
        -0.5,-0.5,0.5, 0,0,1,  0.5,0.5,0.5, 0,0,1, -0.5,0.5,0.5, 0,0,1,
        // Back
        0.5,-0.5,-0.5, 0,0,-1, -0.5,-0.5,-0.5, 0,0,-1, -0.5,0.5,-0.5, 0,0,-1,
        0.5,-0.5,-0.5, 0,0,-1, -0.5,0.5,-0.5, 0,0,-1,  0.5,0.5,-0.5, 0,0,-1,
        // Top
        -0.5,0.5,0.5, 0,1,0,  0.5,0.5,0.5, 0,1,0,  0.5,0.5,-0.5, 0,1,0,
        -0.5,0.5,0.5, 0,1,0,  0.5,0.5,-0.5, 0,1,0, -0.5,0.5,-0.5, 0,1,0,
        // Bottom
        -0.5,-0.5,-0.5, 0,-1,0, 0.5,-0.5,-0.5, 0,-1,0, 0.5,-0.5,0.5, 0,-1,0,
        -0.5,-0.5,-0.5, 0,-1,0, 0.5,-0.5,0.5, 0,-1,0, -0.5,-0.5,0.5, 0,-1,0,
        // Right
        0.5,-0.5,0.5, 1,0,0,  0.5,-0.5,-0.5, 1,0,0,  0.5,0.5,-0.5, 1,0,0,
        0.5,-0.5,0.5, 1,0,0,  0.5,0.5,-0.5, 1,0,0,  0.5,0.5,0.5, 1,0,0,
        // Left
        -0.5,-0.5,-0.5, -1,0,0, -0.5,-0.5,0.5, -1,0,0, -0.5,0.5,0.5, -1,0,0,
        -0.5,-0.5,-0.5, -1,0,0, -0.5,0.5,0.5, -1,0,0, -0.5,0.5,-0.5, -1,0,0,
    ],
    'layout' => [VIO_FLOAT3, VIO_FLOAT3],
    'topology' => VIO_TRIANGLES,
]);

// Floor plane (large, at y=-0.5)
$floor = vio_mesh($ctx, [
    'vertices' => [
        -5,-0.5,-5, 0,1,0,  5,-0.5,-5, 0,1,0,  5,-0.5,5, 0,1,0,
        -5,-0.5,-5, 0,1,0,  5,-0.5,5, 0,1,0, -5,-0.5,5, 0,1,0,
    ],
    'layout' => [VIO_FLOAT3, VIO_FLOAT3],
    'topology' => VIO_TRIANGLES,
]);

echo "Setup OK - running shadow test...\n";

$identity = [1,0,0,0, 0,1,0,0, 0,0,1,0, 0,0,0,1];
$time = 0;

for ($i = 0; $i < 500; $i++) {
    // Rotating light direction
    $la = $time * 0.02;
    $lx = sin($la) * 0.5;
    $ly = -0.8;
    $lz = cos($la) * 0.5;
    $ll = sqrt($lx*$lx + $ly*$ly + $lz*$lz);
    $lx /= $ll; $ly /= $ll; $lz /= $ll;

    // Light-space matrix (orthographic from light direction)
    $lightPos = [-$lx*10, -$ly*10, -$lz*10];
    // Simple lookAt toward origin
    $fwd = [$lx, $ly, $lz]; // normalized light dir
    $up = [0, 0, 1];
    if (abs($ly) < 0.9) $up = [0, 1, 0];
    // cross(up, fwd)
    $rx = $up[1]*$fwd[2] - $up[2]*$fwd[1];
    $ry = $up[2]*$fwd[0] - $up[0]*$fwd[2];
    $rz = $up[0]*$fwd[1] - $up[1]*$fwd[0];
    $rl = sqrt($rx*$rx+$ry*$ry+$rz*$rz);
    $rx/=$rl; $ry/=$rl; $rz/=$rl;
    // cross(fwd, right)
    $ux = $fwd[1]*$rz - $fwd[2]*$ry;
    $uy = $fwd[2]*$rx - $fwd[0]*$rz;
    $uz = $fwd[0]*$ry - $fwd[1]*$rx;

    $lightView = [
        $rx, $ux, $fwd[0], 0,
        $ry, $uy, $fwd[1], 0,
        $rz, $uz, $fwd[2], 0,
        -($rx*$lightPos[0]+$ry*$lightPos[1]+$rz*$lightPos[2]),
        -($ux*$lightPos[0]+$uy*$lightPos[1]+$uz*$lightPos[2]),
        -($fwd[0]*$lightPos[0]+$fwd[1]*$lightPos[1]+$fwd[2]*$lightPos[2]),
        1
    ];

    $s = 8.0;
    $lightProj = [1/$s,0,0,0, 0,1/$s,0,0, 0,0,-1/20,0, 0,0,0,1];

    // Multiply lightProj * lightView (column-major)
    $lsm = array_fill(0, 16, 0.0);
    for ($r = 0; $r < 4; $r++) {
        for ($c = 0; $c < 4; $c++) {
            for ($k = 0; $k < 4; $k++) {
                $lsm[$c*4+$r] += $lightProj[$k*4+$r] * $lightView[$c*4+$k];
            }
        }
    }

    vio_begin($ctx);

    // === SHADOW PASS ===
    vio_bind_render_target($ctx, $shadow_rt);
    vio_viewport($ctx, 0, 0, 1024, 1024);
    vio_clear($ctx, 1, 1, 1, 1);
    vio_bind_pipeline($ctx, $shadow_pipeline);

    vio_set_uniform($ctx, 'u_view', $lsm);
    vio_set_uniform($ctx, 'u_projection', $identity);

    // Draw cube into shadow map
    vio_set_uniform($ctx, 'u_model', [1,0,0,0, 0,1,0,0, 0,0,1,0, 0,1,0,1]); // cube at y=1
    vio_draw($ctx, $cube);

    vio_unbind_render_target($ctx);

    // === MAIN PASS ===
    vio_viewport($ctx, 0, 0, 800, 600);
    vio_clear($ctx, 0.4, 0.6, 0.85);
    vio_bind_pipeline($ctx, $main_pipeline);

    $aspect = 800/600;
    $fov = 1.0;
    $proj = [$fov/$aspect,0,0,0, 0,$fov,0,0, 0,0,-1.002,-1, 0,0,-0.2,0];
    $view = [1,0,0,0, 0,0.85,-0.53,0, 0,0.53,0.85,0, 0,-3.3,-7.5,1];

    vio_set_uniform($ctx, 'u_view', $view);
    vio_set_uniform($ctx, 'u_projection', $proj);
    vio_set_uniform($ctx, 'u_light_dir', [$lx, $ly, $lz]);
    vio_set_uniform($ctx, 'u_has_shadow_map', 1);
    vio_set_uniform($ctx, 'u_light_space_matrix', $lsm);

    $shadowTex = vio_render_target_texture($shadow_rt);
    vio_bind_texture($ctx, $shadowTex, 0);
    vio_set_uniform($ctx, 'u_shadow_map', 0);

    // Draw cube
    vio_set_uniform($ctx, 'u_model', [1,0,0,0, 0,1,0,0, 0,0,1,0, 0,1,0,1]);
    vio_set_uniform($ctx, 'u_albedo', [0.9, 0.3, 0.1]);
    vio_draw($ctx, $cube);

    // Draw floor
    vio_set_uniform($ctx, 'u_model', $identity);
    vio_set_uniform($ctx, 'u_albedo', [0.8, 0.8, 0.7]);
    vio_draw($ctx, $floor);

    vio_end($ctx);
    vio_poll_events($ctx);
    usleep(16666);
    $time++;
}

echo "Done - did the shadow rotate around the cube?\n";
vio_destroy($ctx);
