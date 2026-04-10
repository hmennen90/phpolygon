<?php

/**
 * PHPStan stubs for ext-vio (php-vio).
 */

class VioContext {}
class VioShader {}
class VioPipeline {}
class VioMesh {}
class VioBuffer {}
class VioTexture {}
class VioFont {}

/** @return array{int, int} */
function vio_window_size(VioContext $ctx): array {}

/** @return array{int, int} */
function vio_framebuffer_size(VioContext $ctx): array {}

/** @return array{float, float} */
function vio_content_scale(VioContext $ctx): array {}

function vio_pixel_ratio(VioContext $ctx): float {}

/** @return array{float, float} */
function vio_mouse_position(VioContext $ctx): array {}

/** @return array{float, float} */
function vio_mouse_scroll(VioContext $ctx): array {}

function vio_mouse_button(VioContext $ctx, int $button): bool {}

function vio_key_pressed(VioContext $ctx, int $key): bool {}

function vio_should_close(VioContext $ctx): bool {}

function vio_close(VioContext $ctx): void {}

function vio_poll_events(VioContext $ctx): void {}

function vio_begin(VioContext $ctx): void {}

function vio_end(VioContext $ctx): void {}

function vio_clear(VioContext $ctx, float $r, float $g, float $b, float $a): void {}

function vio_draw_2d(VioContext $ctx): void {}

function vio_destroy(VioContext $ctx): void {}

function vio_set_title(VioContext $ctx, string $title): void {}

function vio_set_fullscreen(VioContext $ctx): void {}

function vio_set_borderless(VioContext $ctx): void {}

function vio_set_windowed(VioContext $ctx): void {}

/**
 * @param array<string, mixed> $config
 * @return VioContext|false
 */
function vio_create(string $backend, array $config): VioContext|false {}

/** @param callable(int, int, int): void $callback */
function vio_on_key(VioContext $ctx, callable $callback): void {}

/** @param callable(int): void $callback */
function vio_on_char(VioContext $ctx, callable $callback): void {}

/**
 * @param array<string, mixed> $desc
 * @return VioShader|false
 */
function vio_shader(VioContext $ctx, array $desc): VioShader|false {}

/**
 * @param array<string, mixed> $desc
 * @return VioPipeline|false
 */
function vio_pipeline(VioContext $ctx, array $desc): VioPipeline|false {}

/**
 * @param array<string, mixed> $desc
 * @return VioMesh|false
 */
function vio_mesh(VioContext $ctx, array $desc): VioMesh|false {}

function vio_bind_pipeline(VioContext $ctx, VioPipeline $pipeline): void {}

function vio_draw(VioContext $ctx, VioMesh $mesh): void {}

function vio_set_uniform(VioContext $ctx, string $name, int|float|array $value): void {}

/**
 * @param array<string, mixed> $desc
 * @return VioTexture|false
 */
function vio_texture(VioContext $ctx, array $desc): VioTexture|false {}

/** @return array{int, int} */
function vio_texture_size(VioTexture $tex): array {}

/**
 * @param array<string, mixed> $options
 * @return VioFont|false
 */
function vio_font(VioContext $ctx, string $path, float $size, array $options = []): VioFont|false {}

/**
 * @param array<string, mixed> $options
 * @return array{width: float, height: float}
 */
function vio_text_measure(VioFont $font, string $text, array $options = []): array {}

/** @param array<string, mixed> $options */
function vio_text(VioContext $ctx, VioFont $font, string $text, float $x, float $y, array $options = []): void {}

/** @param array<string, mixed> $options */
function vio_rect(VioContext $ctx, float $x, float $y, float $w, float $h, array $options = []): void {}

/** @param array<string, mixed> $options */
function vio_sprite(VioContext $ctx, VioTexture $tex, array $options = []): void {}
