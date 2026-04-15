<?php
error_reporting(E_ALL);

$ctx = vio_create('d3d11', ['width' => 100, 'height' => 100, 'title' => 'Reflect Debug']);

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
    v_worldPos = (u_model * vec4(a_position, 1.0)).xyz;
    v_normal = u_normal_matrix * a_normal;
    gl_Position = u_projection * u_view * vec4(v_worldPos, 1.0);
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

out vec4 frag_color;

void main() {
    frag_color = vec4(u_albedo, u_alpha);
}
GLSL;

$shader = vio_shader($ctx, ['vertex' => $vs, 'fragment' => $fs, 'format' => VIO_SHADER_GLSL_RAW]);
if ($shader === false) { echo "SHADER FAILED\n"; vio_destroy($ctx); exit(1); }

$reflect = vio_shader_reflect($shader);

echo "=== VERTEX ===\n";
echo "Inputs: " . count($reflect['vertex']['inputs'] ?? []) . "\n";
foreach ($reflect['vertex']['inputs'] ?? [] as $inp) {
    echo "  loc={$inp['location']} {$inp['name']}\n";
}
echo "UBOs: " . count($reflect['vertex']['ubos'] ?? []) . "\n";
foreach ($reflect['vertex']['ubos'] ?? [] as $ubo) {
    echo "  set={$ubo['set']} binding={$ubo['binding']} '{$ubo['name']}'\n";
}

echo "\n=== FRAGMENT ===\n";
echo "UBOs: " . count($reflect['fragment']['ubos'] ?? []) . "\n";
foreach ($reflect['fragment']['ubos'] ?? [] as $ubo) {
    echo "  set={$ubo['set']} binding={$ubo['binding']} '{$ubo['name']}'\n";
}

echo "\nDone.\n";
vio_destroy($ctx);
