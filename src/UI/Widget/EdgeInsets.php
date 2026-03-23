<?php

declare(strict_types=1);

namespace PHPolygon\UI\Widget;

class EdgeInsets
{
    public function __construct(
        public readonly float $top = 0.0,
        public readonly float $right = 0.0,
        public readonly float $bottom = 0.0,
        public readonly float $left = 0.0,
    ) {}

    public static function all(float $value): self
    {
        return new self($value, $value, $value, $value);
    }

    public static function symmetric(float $horizontal = 0.0, float $vertical = 0.0): self
    {
        return new self($vertical, $horizontal, $vertical, $horizontal);
    }

    public static function zero(): self
    {
        return new self();
    }

    public function horizontal(): float
    {
        return $this->left + $this->right;
    }

    public function vertical(): float
    {
        return $this->top + $this->bottom;
    }
}
