#version 450

layout(location = 0) in vec3 v_normal;
layout(location = 1) in vec3 v_worldPos;
layout(location = 2) in vec2 v_uv;

struct PointLight {
    vec3  position;
    float intensity;
    vec3  color;
    float radius;
};

// Per-frame lighting uniforms
layout(binding = 1) uniform LightingUBO {
    vec3  u_ambient_color;
    float u_ambient_intensity;

    vec3  u_dir_light_direction;
    float u_dir_light_intensity;
    vec3  u_dir_light_color;
    float _pad0;

    vec3  u_albedo;
    float _pad1;

    vec3  u_fog_color;
    float u_fog_near;

    vec3  u_camera_pos;
    float u_fog_far;

    int   u_point_light_count;
    float _pad2;
    float _pad3;
    float _pad4;

    PointLight u_point_lights[8];
};

layout(location = 0) out vec4 frag_color;

void main() {
    vec3 N = normalize(v_normal);

    // Ambient
    vec3 color = u_ambient_color * u_ambient_intensity * u_albedo;

    // Directional light (Lambert)
    float NdotL = max(dot(N, -normalize(u_dir_light_direction)), 0.0);
    color += u_albedo * u_dir_light_color * u_dir_light_intensity * NdotL;

    // Point lights
    for (int i = 0; i < u_point_light_count; i++) {
        vec3 L = u_point_lights[i].position - v_worldPos;
        float dist = length(L);
        float atten = max(0.0, 1.0 - dist / u_point_lights[i].radius);
        float NdotPL = max(dot(N, normalize(L)), 0.0);
        color += u_albedo * u_point_lights[i].color * u_point_lights[i].intensity * NdotPL * atten;
    }

    // Fog
    float fogDist = length(v_worldPos - u_camera_pos);
    float fogFactor = clamp((fogDist - u_fog_near) / (u_fog_far - u_fog_near), 0.0, 1.0);
    color = mix(color, u_fog_color, fogFactor);

    frag_color = vec4(color, 1.0);
}
