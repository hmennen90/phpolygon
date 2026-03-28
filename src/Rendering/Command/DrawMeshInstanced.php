<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Command;

use PHPolygon\Math\Mat4;

readonly class DrawMeshInstanced
{
    /**
     * @param Mat4[] $matrices
     */
    public function __construct(
        public string $meshId,
        public string $materialId,
        public array $matrices,
    ) {}
}
