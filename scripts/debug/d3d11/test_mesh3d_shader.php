<?php
/**
 * Test PHPolygon's mesh3d-like shader with struct array uniforms on D3D11.
 * This tests the most complex uniform patterns PHPolygon uses.
 */
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 800, 'height' => 600, 'title' => 'mesh3d Shader Test']);
echo 'Backend: ' . vio_backend_name($ctx) . PHP_EOL;

$vs = <<<'GLSL'
#version 410 core
layout(location = 0) in vec3 a_position;
layout(location = 1) in vec3 a_normal;
layout(location = 2) in vec2 a_uv;

uniform mat4 u_model;
uniform mat4 u_view;
uniform mat4 u_projection;
uniform mat3 u_normal_matrix;
uniform int  u_use_instancing;
uniform float u_time;

out vec3 v_normal;
out vec3 v_worldPos;

void main() {
    mat4 model = u_model;
    vec4 worldPos = model * vec4(a_position, 1.0);
    v_worldPos = worldPos.xyz;
    v_normal = u_normal_matrix * a_normal;
    gl_Position = u_projection * u_view * worldPos;
}
GLSL;

$fs = <<<'GLSL'
#version 410 core

in vec3 v_normal;
in vec3 v_worldPos;

uniform vec3 u_ambient_color;
uniform float u_ambient_intensity;

struct DirLight {
    vec3 direction;
    vec3 color;
    float intensity;
};
uniform DirLight u_dir_lights[4];
uniform int u_dir_light_count;

struct PointLight {
    vec3 position;
    vec3 color;
    float intensity;
    float radius;
};
uniform PointLight u_point_lights[4];
uniform int u_point_light_count;

uniform vec3 u_albedo;
uniform vec3 u_emission;
uniform float u_roughness;
uniform float u_metallic;
uniform float u_alpha;
uniform vec3 u_fog_color;
uniform float u_fog_near;
uniform float u_fog_far;
uniform vec3 u_camera_pos;
uniform float u_time;

out vec4 frag_color;

void main() {
    vec3 N = normalize(v_normal);
    vec3 color = u_albedo * u_ambient_color * u_ambient_intensity;

    // Directional lights
    for (int i = 0; i < u_dir_light_count; i++) {
        float diff = max(dot(N, -u_dir_lights[i].direction), 0.0);
        color += u_albedo * u_dir_lights[i].color * diff * u_dir_lights[i].intensity;
    }

    // Point lights
    for (int i = 0; i < u_point_light_count; i++) {
        vec3 lightDir = u_point_lights[i].position - v_worldPos;
        float dist = length(lightDir);
        float atten = clamp(1.0 - dist / u_point_lights[i].radius, 0.0, 1.0);
        float diff = max(dot(N, normalize(lightDir)), 0.0);
        color += u_albedo * u_point_lights[i].color * diff * u_point_lights[i].intensity * atten;
    }

    color += u_emission;

    // Fog
    float dist = length(v_worldPos - u_camera_pos);
    float fogFactor = clamp((dist - u_fog_near) / (u_fog_far - u_fog_near), 0.0, 1.0);
    color = mix(color, u_fog_color, fogFactor);

    frag_color = vec4(color, u_alpha);
}
GLSL;

$shader = vio_shader($ctx, ['vertex' => $vs, 'fragment' => $fs, 'format' => VIO_SHADER_GLSL_RAW]);
if ($shader === false) { echo "SHADER FAILED\n"; vio_destroy($ctx); exit(1); }
echo "Shader compiled OK\n";

$pipeline = vio_pipeline($ctx, [
    'shader' => $shader,
    'depth_test' => true,
    'cull_mode' => VIO_CULL_NONE,
]);
if ($pipeline === false) { echo "PIPELINE FAILED\n"; vio_destroy($ctx); exit(1); }
echo "Pipeline OK\n";

// Simple sphere approximation (icosphere-like)
$verts = [];
$slices = 16; $stacks = 12;
for ($st = 0; $st < $stacks; $st++) {
    $phi1 = M_PI * $st / $stacks;
    $phi2 = M_PI * ($st + 1) / $stacks;
    for ($sl = 0; $sl < $slices; $sl++) {
        $th1 = 2 * M_PI * $sl / $slices;
        $th2 = 2 * M_PI * ($sl + 1) / $slices;

        $p = [
            [sin($phi1)*cos($th1), cos($phi1), sin($phi1)*sin($th1)],
            [sin($phi2)*cos($th1), cos($phi2), sin($phi2)*sin($th1)],
            [sin($phi2)*cos($th2), cos($phi2), sin($phi2)*sin($th2)],
            [sin($phi1)*cos($th2), cos($phi1), sin($phi1)*sin($th2)],
        ];

        // Two triangles per quad
        foreach ([[0,1,2],[0,2,3]] as $tri) {
            foreach ($tri as $idx) {
                $verts = array_merge($verts, $p[$idx]); // pos
                $verts = array_merge($verts, $p[$idx]); // normal = pos (unit sphere)
                $verts[] = 0; $verts[] = 0; // uv
            }
        }
    }
}

$mesh = vio_mesh($ctx, [
    'vertices' => $verts,
    'layout' => [VIO_FLOAT3, VIO_FLOAT3, VIO_FLOAT2],
    'topology' => VIO_TRIANGLES,
]);
echo "Mesh: " . (count($verts) / 8) . " vertices\n";

$time = 0;
for ($i = 0; $i < 500; $i++) {
    vio_begin($ctx);
    vio_clear($ctx, 0.05, 0.08, 0.15);
    vio_bind_pipeline($ctx, $pipeline);

    // Matrices
    $fov = 1.0; $aspect = 800.0 / 600.0;
    $proj = [$fov/$aspect,0,0,0, 0,$fov,0,0, 0,0,-1.002,-1, 0,0,-0.2002,0];
    $view = [1,0,0,0, 0,1,0,0, 0,0,1,0, 0,0,-4,1];

    $angle = $time * 0.01;
    $c = cos($angle); $s = sin($angle);
    $model = [$c,0,$s,0, 0,1,0,0, -$s,0,$c,0, 0,0,0,1];

    // Vertex uniforms
    vio_set_uniform($ctx, 'u_model', $model);
    vio_set_uniform($ctx, 'u_view', $view);
    vio_set_uniform($ctx, 'u_projection', $proj);
    vio_set_uniform($ctx, 'u_normal_matrix', [[$c,0,$s],[0,1,0],[-$s,0,$c]]);
    vio_set_uniform($ctx, 'u_use_instancing', 0);
    vio_set_uniform($ctx, 'u_time', $time * 0.016);

    // Fragment uniforms — ambient
    vio_set_uniform($ctx, 'u_ambient_color', [0.3, 0.3, 0.4]);
    vio_set_uniform($ctx, 'u_ambient_intensity', 0.3);

    // Directional light (sun)
    vio_set_uniform($ctx, 'u_dir_light_count', 1);
    vio_set_uniform($ctx, 'u_dir_lights[0].direction', [0.3, -0.8, -0.5]);
    vio_set_uniform($ctx, 'u_dir_lights[0].color', [1.0, 0.95, 0.8]);
    vio_set_uniform($ctx, 'u_dir_lights[0].intensity', 1.2);

    // Point light (orbiting)
    $lx = cos($time * 0.03) * 3.0;
    $lz = sin($time * 0.03) * 3.0;
    vio_set_uniform($ctx, 'u_point_light_count', 1);
    vio_set_uniform($ctx, 'u_point_lights[0].position', [$lx, 1.0, $lz]);
    vio_set_uniform($ctx, 'u_point_lights[0].color', [0.2, 0.5, 1.0]);
    vio_set_uniform($ctx, 'u_point_lights[0].intensity', 2.0);
    vio_set_uniform($ctx, 'u_point_lights[0].radius', 8.0);

    // Material
    vio_set_uniform($ctx, 'u_albedo', [0.8, 0.4, 0.1]);   // orange-brown
    vio_set_uniform($ctx, 'u_emission', [0.0, 0.0, 0.0]);
    vio_set_uniform($ctx, 'u_roughness', 0.7);
    vio_set_uniform($ctx, 'u_metallic', 0.0);
    vio_set_uniform($ctx, 'u_alpha', 1.0);

    // Fog
    vio_set_uniform($ctx, 'u_fog_color', [0.05, 0.08, 0.15]);
    vio_set_uniform($ctx, 'u_fog_near', 20.0);
    vio_set_uniform($ctx, 'u_fog_far', 100.0);
    vio_set_uniform($ctx, 'u_camera_pos', [0.0, 0.0, 4.0]);
    vio_set_uniform($ctx, 'u_time', $time * 0.016);

    vio_draw($ctx, $mesh);
    vio_end($ctx);
    vio_poll_events($ctx);
    usleep(16666);
    $time++;
}

echo "Done - did you see a lit, shaded sphere with directional + point light?\n";
vio_destroy($ctx);
