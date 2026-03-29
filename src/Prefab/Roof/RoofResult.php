<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Roof;

class RoofResult
{
    /**
     * @param float $ridgeY        Ridge peak world Y
     * @param float $eaveY         Eave (base) world Y
     * @param float $frontWallTopY Roof underside Y at the front wall position — walls should reach this height
     * @param float $backWallTopY  Roof underside Y at the back wall position
     * @param int   $entityCount   Number of entities created
     */
    public function __construct(
        public readonly float $ridgeY,
        public readonly float $eaveY,
        public readonly int $entityCount,
        public readonly float $frontWallTopY = 0.0,
        public readonly float $backWallTopY = 0.0,
    ) {}
}
