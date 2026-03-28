<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Command;

use PHPolygon\Rendering\Color;

readonly class SetFog
{
    public function __construct(
        public Color $color,
        public float $near,
        public float $far,
    ) {}
}
