<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Furniture;

class FurnitureResult
{
    /** @param list<string> $entityNames */
    public function __construct(
        public readonly int $entityCount,
        public readonly array $entityNames = [],
    ) {}
}
