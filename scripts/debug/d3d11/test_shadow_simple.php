<?php
/**
 * Simplest possible shadow map test on D3D11.
 * A triangle casts shadow on a floor quad.
 * Main shader outputs darkened color where in shadow.
 */
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 800, 'height' => 600, 'title' => 'Simple Shadow']);

// === SHADOW SHADER (depth-only) ===
$shadow_vs = <<<'GLSL'
#version 410 core
layout(location = 0) in vec3 a_position;
layout(location = 1) in vec3 a_normal;
layout(location = 2) in vec2 a_uv;
uniform mat4 u_mvp;
void main() {
    gl_Position = u_mvp * vec4(a_position, 1.0);
}
GLSL;

$shadow_fs = <<<'GLSL'
#version 410 core
void main() {}
GLSL;

// === MAIN SHADER (manual shadow comparison via sampler2D) ===
$main_vs = <<<'GLSL'
#version 410 core
layout(location = 0) in vec3 a_position;
layout(location = 1) in vec3 a_normal;
layout(location = 2) in vec2 a_uv;
uniform mat4 u_mvp;
uniform mat4 u_light_mvp;
out vec4 v_lightSpacePos;
out vec3 v_normal;
void main() {
    gl_Position = u_mvp * vec4(a_position, 1.0);
    v_lightSpacePos = u_light_mvp * vec4(a_position, 1.0);
    v_normal = a_normal;
}
GLSL;

$main_fs = <<<'GLSL'
#version 410 core
in vec4 v_lightSpacePos;
in vec3 v_normal;
uniform sampler2D u_shadow_map;
uniform vec3 u_color;
out vec4 frag_color;
void main() {
    // Manual shadow comparison
    vec3 proj = v_lightSpacePos.xyz / v_lightSpacePos.w;
    proj = proj * 0.5 + 0.5;

    float shadow = 1.0;
    if (proj.x >= 0.0 && proj.x <= 1.0 && proj.y >= 0.0 && proj.y <= 1.0 && proj.z <= 1.0) {
        float depthInMap = texture(u_shadow_map, proj.xy).r;
        if (proj.z - 0.005 > depthInMap) {
            shadow = 0.3;
        }
    }

    // Simple directional lighting
    float ndl = max(dot(normalize(v_normal), normalize(vec3(0.3, 1.0, 0.5))), 0.2);

    frag_color = vec4(u_color * shadow * ndl, 1.0);
}
GLSL;

$shadow_shader = vio_shader($ctx, ['vertex' => $shadow_vs, 'fragment' => $shadow_fs, 'format' => VIO_SHADER_GLSL_RAW]);
$main_shader = vio_shader($ctx, ['vertex' => $main_vs, 'fragment' => $main_fs, 'format' => VIO_SHADER_GLSL_RAW]);
if (!$shadow_shader || !$main_shader) { echo "SHADER FAILED\n"; exit(1); }
echo "Shaders OK\n";

$shadow_pipe = vio_pipeline($ctx, [
    'shader' => $shadow_shader,
    'depth_test' => true,
    'cull_mode' => VIO_CULL_NONE,
    'depth_bias' => 25.0,
    'slope_scaled_depth_bias' => 2.0,
]);
$main_pipe = vio_pipeline($ctx, ['shader' => $main_shader, 'depth_test' => true, 'cull_mode' => VIO_CULL_NONE]);

$shadow_rt = vio_render_target($ctx, ['width' => 1024, 'height' => 1024, 'depth_only' => true]);
if (!$shadow_rt) { echo "RT FAILED\n"; exit(1); }
echo "RT OK\n";

// Triangle (occluder) floating above floor - pos + normal + uv
$tri = vio_mesh($ctx, [
    'vertices' => [
        -1.0, 2.0, -0.5,  0,1,0, 0,0,
         1.0, 2.0, -0.5,  0,1,0, 1,0,
         0.0, 2.0,  0.5,  0,1,0, 0.5,1,
    ],
    'layout' => [VIO_FLOAT3, VIO_FLOAT3, VIO_FLOAT2],
    'topology' => VIO_TRIANGLES,
]);

// Floor at y=0 - pos + normal + uv
$floor = vio_mesh($ctx, [
    'vertices' => [
        -4, 0, -4,  0,1,0, 0,0,
         4, 0, -4,  0,1,0, 1,0,
         4, 0,  4,  0,1,0, 1,1,
        -4, 0, -4,  0,1,0, 0,0,
         4, 0,  4,  0,1,0, 1,1,
        -4, 0,  4,  0,1,0, 0,1,
    ],
    'layout' => [VIO_FLOAT3, VIO_FLOAT3, VIO_FLOAT2],
    'topology' => VIO_TRIANGLES,
]);

echo "Meshes OK - running...\n";

// === Helper: build perspective projection (column-major) ===
function perspectiveMatrix(float $fovDeg, float $aspect, float $near, float $far): array {
    $f = 1.0 / tan(deg2rad($fovDeg) / 2.0);
    $nf = 1.0 / ($near - $far);
    return [
        $f / $aspect, 0,   0,                    0,
        0,            $f,  0,                    0,
        0,            0,   ($far + $near) * $nf, -1,
        0,            0,   2 * $far * $near * $nf, 0,
    ];
}

// === Helper: build orthographic projection (column-major) ===
function orthoMatrix(float $l, float $r, float $b, float $t, float $n, float $f): array {
    return [
        2/($r-$l),       0,               0,               0,
        0,                2/($t-$b),       0,               0,
        0,                0,               -2/($f-$n),      0,
        -($r+$l)/($r-$l), -($t+$b)/($t-$b), -($f+$n)/($f-$n), 1,
    ];
}

// === Helper: build look-at view matrix (column-major) ===
function lookAtMatrix(array $eye, array $center, array $up): array {
    $fx = $center[0]-$eye[0]; $fy = $center[1]-$eye[1]; $fz = $center[2]-$eye[2];
    $len = sqrt($fx*$fx + $fy*$fy + $fz*$fz);
    $fx/=$len; $fy/=$len; $fz/=$len;
    // right = cross(f, up)
    $rx = $fy*$up[2] - $fz*$up[1];
    $ry = $fz*$up[0] - $fx*$up[2];
    $rz = $fx*$up[1] - $fy*$up[0];
    $len = sqrt($rx*$rx + $ry*$ry + $rz*$rz);
    $rx/=$len; $ry/=$len; $rz/=$len;
    // real up = cross(right, f)
    $ux = $ry*$fz - $rz*$fy;
    $uy = $rz*$fx - $rx*$fz;
    $uz = $rx*$fy - $ry*$fx;
    return [
        $rx, $ux, -$fx, 0,
        $ry, $uy, -$fy, 0,
        $rz, $uz, -$fz, 0,
        -($rx*$eye[0]+$ry*$eye[1]+$rz*$eye[2]),
        -($ux*$eye[0]+$uy*$eye[1]+$uz*$eye[2]),
        -(-$fx*$eye[0]+-$fy*$eye[1]+-$fz*$eye[2]),
        1,
    ];
}

// === Helper: multiply two 4x4 matrices (column-major) ===
function mat4Mul(array $a, array $b): array {
    $r = array_fill(0, 16, 0.0);
    for ($c = 0; $c < 4; $c++) {
        for ($row = 0; $row < 4; $row++) {
            $sum = 0.0;
            for ($k = 0; $k < 4; $k++) {
                $sum += $a[$k*4+$row] * $b[$c*4+$k];
            }
            $r[$c*4+$row] = $sum;
        }
    }
    return $r;
}

// Light: orthographic from above-right looking at origin
$lightView = lookAtMatrix([4, 8, 4], [0, 0, 0], [0, 1, 0]);
$lightProj = orthoMatrix(-6, 6, -6, 6, 0.1, 20);
$light_mvp = mat4Mul($lightProj, $lightView);

// Camera: perspective from slightly above, looking at origin
$camView = lookAtMatrix([0, 4, 8], [0, 0.5, 0], [0, 1, 0]);
$camProj = perspectiveMatrix(60, 800/600, 0.1, 100);
$cam_mvp = mat4Mul($camProj, $camView);

for ($i = 0; $i < 400; $i++) {
    vio_begin($ctx);

    // === SHADOW PASS ===
    vio_bind_render_target($ctx, $shadow_rt);
    vio_viewport($ctx, 0, 0, 1024, 1024);
    vio_clear($ctx, 1, 1, 1, 1);
    vio_bind_pipeline($ctx, $shadow_pipe);

    vio_set_uniform($ctx, 'u_mvp', $light_mvp);
    vio_draw($ctx, $tri);  // only draw occluder into shadow map

    vio_unbind_render_target($ctx);

    // === MAIN PASS ===
    vio_viewport($ctx, 0, 0, 800, 600);
    vio_clear($ctx, 0.2, 0.3, 0.5);
    vio_bind_pipeline($ctx, $main_pipe);

    $shadowTex = vio_render_target_texture($shadow_rt);
    vio_bind_texture($ctx, $shadowTex, 0);
    vio_set_uniform($ctx, 'u_shadow_map', 0);

    // Draw floor (should show shadow)
    vio_set_uniform($ctx, 'u_mvp', $cam_mvp);
    vio_set_uniform($ctx, 'u_light_mvp', $light_mvp);
    vio_set_uniform($ctx, 'u_color', [0.9, 0.9, 0.8]);
    vio_draw($ctx, $floor);

    // Draw triangle (occluder)
    vio_set_uniform($ctx, 'u_color', [0.8, 0.2, 0.1]);
    vio_draw($ctx, $tri);

    vio_end($ctx);
    vio_poll_events($ctx);
    usleep(16666);
}

echo "Done - floor should show a triangle-shaped shadow\n";
vio_destroy($ctx);
