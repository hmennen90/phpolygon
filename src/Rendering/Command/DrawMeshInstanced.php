<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Command;

use PHPolygon\Math\Mat4;

readonly class DrawMeshInstanced
{
    /**
     * @param Mat4[] $matrices
     * @param bool $isStatic When true, the renderer caches the instance buffer
     *                       on first upload and skips re-upload on subsequent frames.
     */
    public function __construct(
        public string $meshId,
        public string $materialId,
        public array $matrices,
        public bool $isStatic = false,
    ) {}
}
