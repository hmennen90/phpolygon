<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Command;

use PHPolygon\Rendering\Color;

readonly class SetAmbientLight
{
    public function __construct(
        public Color $color,
        public float $intensity,
    ) {}
}
