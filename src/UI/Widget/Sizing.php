<?php

declare(strict_types=1);

namespace PHPolygon\UI\Widget;

/**
 * Size constraints for a widget.
 *
 * - Fixed: exact pixel value (width/height > 0)
 * - Fill: expand to fill parent (width/height = 0, fill = true)
 * - Wrap: shrink to content (default, width/height = 0, fill = false)
 */
class Sizing
{
    public function __construct(
        public float $width = 0.0,
        public float $height = 0.0,
        public float $minWidth = 0.0,
        public float $minHeight = 0.0,
        public float $maxWidth = PHP_FLOAT_MAX,
        public float $maxHeight = PHP_FLOAT_MAX,
        public bool $fillWidth = false,
        public bool $fillHeight = false,
    ) {}

    public static function fixed(float $width, float $height): self
    {
        return new self(width: $width, height: $height);
    }

    public static function fill(): self
    {
        return new self(fillWidth: true, fillHeight: true);
    }

    public static function fillWidth(float $height = 0.0): self
    {
        return new self(height: $height, fillWidth: true);
    }

    public static function fillHeight(float $width = 0.0): self
    {
        return new self(width: $width, fillHeight: true);
    }

    public static function wrap(): self
    {
        return new self();
    }
}
