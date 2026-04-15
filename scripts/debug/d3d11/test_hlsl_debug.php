<?php
error_reporting(E_ALL);

// Test: inspect the HLSL that SPIRV-Cross generates for both stages

$vs_glsl = <<<'GLSL'
#version 410 core
layout(location=0) in vec3 aPos;

uniform mat4 u_model;
uniform mat4 u_view;
uniform mat4 u_projection;

void main() {
    gl_Position = u_projection * u_view * u_model * vec4(aPos, 1.0);
}
GLSL;

$fs_glsl = <<<'GLSL'
#version 410 core
layout(location=0) out vec4 FragColor;

uniform vec3 u_color;
uniform float u_intensity;

void main() {
    FragColor = vec4(u_color * u_intensity, 1.0);
}
GLSL;

// Compile GLSL to SPIRV
$vs_spirv = vio_compile_spirv($vs_glsl, 'vertex');
$fs_spirv = vio_compile_spirv($fs_glsl, 'fragment');

if ($vs_spirv === false) { echo "VS SPIRV compile failed\n"; exit(1); }
if ($fs_spirv === false) { echo "FS SPIRV compile failed\n"; exit(1); }

echo "VS SPIRV: " . strlen($vs_spirv) . " bytes\n";
echo "FS SPIRV: " . strlen($fs_spirv) . " bytes\n";

// Transpile to HLSL
$vs_hlsl = vio_transpile_hlsl($vs_spirv);
$fs_hlsl = vio_transpile_hlsl($fs_spirv);

if ($vs_hlsl === false) { echo "VS HLSL transpile failed\n"; exit(1); }
if ($fs_hlsl === false) { echo "FS HLSL transpile failed\n"; exit(1); }

echo "\n=== VERTEX HLSL ===\n";
echo $vs_hlsl . "\n";

echo "\n=== FRAGMENT HLSL ===\n";
echo $fs_hlsl . "\n";
