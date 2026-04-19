<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Command;

use PHPolygon\Math\Vec3;
use PHPolygon\Rendering\Color;

/**
 * Set atmospheric sky parameters for the current frame. The renderer draws
 * the sky as a fullscreen pass whose fragment shader reconstructs a world-
 * space view ray from the camera and evaluates the atmospheric model from
 * these parameters per pixel — no skybox geometry is involved.
 *
 * The sun disc and glow are baked into the fragment; a separate 3D Sun
 * entity (if any) is drawn as regular opaque geometry on top.
 */
final class SetSky
{
    public function __construct(
        /** Direction FROM surface TOWARD the sun (normalized). */
        public readonly Vec3 $sunDirection,
        public readonly Color $sunColor,
        public readonly float $sunIntensity,
        public readonly Color $zenithColor,
        public readonly Color $horizonColor,
        public readonly Color $groundColor,
        /** Angular radius of the sun disc in radians (≈ 0.004 for real sun). */
        public readonly float $sunSize = 0.02,
        /** Angular extent of the sun glow halo in radians. */
        public readonly float $sunGlowSize = 0.2,
        public readonly float $sunGlowIntensity = 0.3,
        /** Moon direction FROM surface TOWARD the moon. Null = no moon drawn. */
        public readonly ?Vec3 $moonDirection = null,
        public readonly Color $moonColor = new Color(0.85, 0.87, 0.95),
        public readonly float $moonIntensity = 0.0,
        /** 0..1, fraction of sky filled with cloud tone at dusk. */
        public readonly float $cloudCover = 0.0,
        /** 0..1 — brightness of the starfield. 0 = no stars. */
        public readonly float $starBrightness = 0.0,
    ) {}
}
