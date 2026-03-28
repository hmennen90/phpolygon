#version 450

layout(location = 0) in vec3 a_position;
layout(location = 1) in vec3 a_normal;
layout(location = 2) in vec2 a_uv;

// Per-frame: view + projection
layout(binding = 0) uniform FrameUBO {
    mat4 u_view;
    mat4 u_projection;
};

// Per-draw: model matrix via push constant (64 bytes, within guaranteed min 128)
layout(push_constant) uniform PushConstants {
    mat4 u_model;
};

layout(location = 0) out vec3 v_normal;
layout(location = 1) out vec3 v_worldPos;
layout(location = 2) out vec2 v_uv;

void main() {
    vec4 worldPos = u_model * vec4(a_position, 1.0);
    v_worldPos = worldPos.xyz;
    v_normal = mat3(transpose(inverse(u_model))) * a_normal;
    v_uv = a_uv;
    gl_Position = u_projection * u_view * worldPos;
}
