<?php

declare(strict_types=1);

namespace PHPolygon\Rendering;

use PHPolygon\Math\Vec3;

/**
 * Procedural sky generator for cubemap-based skyboxes.
 *
 * Generates an atmosphere gradient with optional sun disc, glow, and starfield.
 * All parameters are code-driven - no texture files needed.
 *
 * Usage:
 *     $sky = new ProceduralSky(
 *         zenithColor:  new Color(0.05, 0.1, 0.4),
 *         horizonColor: new Color(0.6, 0.4, 0.2),
 *         sunDirection: (new Vec3(-0.5, -0.8, -0.3))->normalize(),
 *     );
 *     $cubemap = $sky->generate(512);
 */
class ProceduralSky
{
    public function __construct(
        public readonly Color $zenithColor = new Color(0.05, 0.15, 0.45),
        public readonly Color $horizonColor = new Color(0.55, 0.65, 0.8),
        public readonly Color $groundColor = new Color(0.15, 0.12, 0.1),
        public readonly ?Vec3 $sunDirection = null,
        public readonly Color $sunColor = new Color(1.0, 0.95, 0.8),
        public readonly float $sunSize = 0.04,
        public readonly float $sunGlowSize = 0.25,
        public readonly float $sunGlowIntensity = 0.4,
        public readonly float $starDensity = 0.0,
        public readonly float $starBrightness = 1.0,
        public readonly int $starSeed = 42,
    ) {}

    public function generate(int $resolution = 512): CubemapData
    {
        $zenith = $this->zenithColor;
        $horizon = $this->horizonColor;
        $ground = $this->groundColor;
        $sunDir = $this->sunDirection?->normalize();
        $sunColor = $this->sunColor;
        $sunSize = $this->sunSize;
        $sunGlowSize = $this->sunGlowSize;
        $sunGlowIntensity = $this->sunGlowIntensity;
        $starDensity = $this->starDensity;
        $starBrightness = $this->starBrightness;
        $starSeed = $this->starSeed;

        return ProceduralCubemap::generate($resolution, function (Vec3 $dir) use (
            $zenith, $horizon, $ground, $sunDir, $sunColor,
            $sunSize, $sunGlowSize, $sunGlowIntensity,
            $starDensity, $starBrightness, $starSeed,
        ): Color {
            $elevation = $dir->y;

            // --- Atmosphere gradient ---
            if ($elevation >= 0.0) {
                // Sky: smoothstep from horizon (y=0) to zenith (y=1)
                $t = self::smoothstep(0.0, 1.0, $elevation);
                $r = self::lerp($horizon->r, $zenith->r, $t);
                $g = self::lerp($horizon->g, $zenith->g, $t);
                $b = self::lerp($horizon->b, $zenith->b, $t);
            } else {
                // Below horizon: blend to ground
                $t = self::smoothstep(0.0, -0.3, $elevation);
                $r = self::lerp($horizon->r, $ground->r, $t);
                $g = self::lerp($horizon->g, $ground->g, $t);
                $b = self::lerp($horizon->b, $ground->b, $t);
            }

            // --- Sun disc + glow ---
            if ($sunDir !== null) {
                $dot = $dir->x * $sunDir->x + $dir->y * $sunDir->y + $dir->z * $sunDir->z;
                $angle = acos(max(-1.0, min(1.0, $dot)));

                // Hard sun disc
                if ($angle < $sunSize) {
                    $edgeFade = self::smoothstep($sunSize, $sunSize * 0.5, $angle);
                    $r = self::lerp($r, $sunColor->r, $edgeFade);
                    $g = self::lerp($g, $sunColor->g, $edgeFade);
                    $b = self::lerp($b, $sunColor->b, $edgeFade);
                }

                // Soft glow around sun
                if ($angle < $sunGlowSize) {
                    $glowFactor = (1.0 - $angle / $sunGlowSize);
                    $glowFactor = $glowFactor * $glowFactor * $sunGlowIntensity;
                    $r += $sunColor->r * $glowFactor;
                    $g += $sunColor->g * $glowFactor;
                    $b += $sunColor->b * $glowFactor;
                }

                // Horizon scatter: warm color near sun at horizon level
                if ($elevation > -0.1 && $elevation < 0.3) {
                    $horizonBand = 1.0 - abs($elevation - 0.05) / 0.25;
                    $horizonBand = max(0.0, $horizonBand);
                    $scatter = max(0.0, $dot) * $horizonBand * 0.3;
                    $r += $sunColor->r * $scatter;
                    $g += $sunColor->g * $scatter * 0.6;
                    $b += $sunColor->b * $scatter * 0.3;
                }
            }

            // --- Stars ---
            if ($starDensity > 0.0 && $elevation > 0.0) {
                $star = self::starNoise($dir, $starSeed);
                if ($star < $starDensity) {
                    $intensity = (1.0 - $star / $starDensity) * $starBrightness;
                    // Stars fade near horizon (atmospheric extinction)
                    $intensity *= self::smoothstep(0.0, 0.15, $elevation);
                    $r += $intensity;
                    $g += $intensity;
                    $b += $intensity;
                }
            }

            return new Color(
                min(1.0, max(0.0, $r)),
                min(1.0, max(0.0, $g)),
                min(1.0, max(0.0, $b)),
            );
        });
    }

    /**
     * Create a preset for a clear daytime sky.
     */
    public static function day(Vec3 $sunDirection): self
    {
        return new self(
            zenithColor: new Color(0.15, 0.3, 0.7),
            horizonColor: new Color(0.55, 0.7, 0.9),
            groundColor: new Color(0.2, 0.18, 0.15),
            sunDirection: $sunDirection,
            sunColor: new Color(1.0, 0.97, 0.85),
            sunSize: 0.03,
            sunGlowSize: 0.2,
            sunGlowIntensity: 0.3,
        );
    }

    /**
     * Create a preset for sunset/sunrise.
     */
    public static function sunset(Vec3 $sunDirection): self
    {
        return new self(
            zenithColor: new Color(0.1, 0.12, 0.35),
            horizonColor: new Color(0.8, 0.4, 0.15),
            groundColor: new Color(0.15, 0.08, 0.05),
            sunDirection: $sunDirection,
            sunColor: new Color(1.0, 0.7, 0.3),
            sunSize: 0.045,
            sunGlowSize: 0.35,
            sunGlowIntensity: 0.6,
        );
    }

    /**
     * Create a preset for a clear night sky with stars.
     */
    public static function night(?Vec3 $moonDirection = null): self
    {
        return new self(
            zenithColor: new Color(0.01, 0.01, 0.04),
            horizonColor: new Color(0.03, 0.04, 0.08),
            groundColor: new Color(0.02, 0.02, 0.02),
            sunDirection: $moonDirection,
            sunColor: new Color(0.8, 0.85, 0.95),
            sunSize: 0.02,
            sunGlowSize: 0.08,
            sunGlowIntensity: 0.15,
            starDensity: 0.002,
            starBrightness: 0.8,
        );
    }

    /**
     * Create a preset for an overcast sky.
     */
    public static function overcast(): self
    {
        return new self(
            zenithColor: new Color(0.45, 0.47, 0.5),
            horizonColor: new Color(0.55, 0.55, 0.56),
            groundColor: new Color(0.25, 0.24, 0.22),
        );
    }

    private static function lerp(float $a, float $b, float $t): float
    {
        return $a + ($b - $a) * $t;
    }

    private static function smoothstep(float $edge0, float $edge1, float $x): float
    {
        if ($edge0 === $edge1) {
            return $x >= $edge0 ? 1.0 : 0.0;
        }
        $t = max(0.0, min(1.0, ($x - $edge0) / ($edge1 - $edge0)));
        return $t * $t * (3.0 - 2.0 * $t);
    }

    /**
     * Hash-based star noise. Returns a pseudo-random value in [0, 1) for a given direction.
     * Quantizes direction to a grid so stars appear as discrete points.
     */
    private static function starNoise(Vec3 $dir, int $seed): float
    {
        // Quantize to angular grid (~4000 cells across the sky)
        $scale = 200.0;
        $ix = (int)floor($dir->x * $scale);
        $iy = (int)floor($dir->y * $scale);
        $iz = (int)floor($dir->z * $scale);

        // Simple integer hash. Mask each intermediate to 32 bits so 64-bit
        // multiplications don't overflow into float territory — PHP's >>
        // operator errors out if the left operand became a float.
        $mask = 0xFFFFFFFF;
        $h = (int)(($ix * 374761393 + $iy * 668265263 + $iz * 1274126177 + $seed) & $mask);
        $h = (int)((($h ^ (($h >> 13) & $mask)) * 1103515245) & $mask);
        $h = (int)(($h ^ (($h >> 16) & $mask)) & $mask);

        return ($h & 0xFFFF) / 65536.0;
    }
}
