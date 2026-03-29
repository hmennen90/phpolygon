<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Command;

/**
 * Binds an environment cubemap for reflections.
 * When set, materials with reflective properties (water, metal) sample this cubemap.
 */
class SetEnvironmentMap
{
    public function __construct(
        public readonly int $textureId,  // OpenGL cubemap texture ID
    ) {}
}
