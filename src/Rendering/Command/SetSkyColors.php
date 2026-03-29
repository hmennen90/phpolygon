<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Command;

use PHPolygon\Rendering\Color;

/**
 * Sets the sky and horizon colors used for reflection fallback (when no cubemap).
 * Also used by the water shader for dynamic sky-colored reflections.
 */
class SetSkyColors
{
    public function __construct(
        public readonly Color $skyColor,
        public readonly Color $horizonColor,
    ) {}
}
