<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Command;

use PHPolygon\Math\Mat4;

readonly class SetCamera
{
    public function __construct(
        public Mat4 $viewMatrix,
        public Mat4 $projectionMatrix,
    ) {}
}
