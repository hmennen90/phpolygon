<?php

declare(strict_types=1);

namespace PHPolygon\Physics;

use PHPolygon\Math\Vec3;

/**
 * Represents a collision between two entities.
 */
readonly class Collision3D
{
    public function __construct(
        public int $entityA,
        public int $entityB,
        public Vec3 $normal,       // From A to B
        public float $penetration,
        public Vec3 $contactPoint,
    ) {}
}
