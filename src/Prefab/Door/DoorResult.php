<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Door;

class DoorResult
{
    /**
     * @param int $entityCount Number of entities created
     * @param list<string> $entityNames Names of created entities
     */
    public function __construct(
        public readonly int $entityCount,
        public readonly array $entityNames = [],
    ) {}
}
