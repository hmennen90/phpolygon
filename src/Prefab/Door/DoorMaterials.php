<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Door;

class DoorMaterials
{
    public readonly string $frame;
    public readonly string $handle;

    public function __construct(
        public readonly string $panel,
        ?string $frame = null,
        ?string $handle = null,
    ) {
        $this->frame = $frame ?? $panel;
        $this->handle = $handle ?? $panel;
    }
}
