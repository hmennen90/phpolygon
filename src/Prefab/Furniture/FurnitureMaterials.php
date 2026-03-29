<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Furniture;

class FurnitureMaterials
{
    public readonly string $secondary;
    public readonly string $fabric;
    public readonly string $metal;

    public function __construct(
        public readonly string $primary,
        ?string $secondary = null,
        ?string $fabric = null,
        ?string $metal = null,
    ) {
        $this->secondary = $secondary ?? $primary;
        $this->fabric = $fabric ?? $primary;
        $this->metal = $metal ?? $primary;
    }
}
