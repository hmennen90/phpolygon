<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Command;

/**
 * How wet the ground appears. Drives the procedural sand shader's wetness
 * gradient and rain puddles. 0 = bone dry, 1 = soaked / fully wet.
 *
 * Issued by the environmental system each frame from Weather.rainIntensity.
 */
final readonly class SetGroundWetness
{
    public function __construct(
        public float $rainWetness,
    ) {}
}
