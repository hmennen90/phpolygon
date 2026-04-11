<?php

declare(strict_types=1);

namespace PHPolygon\Rendering;

use PHPolygon\Math\Vec3;

/**
 * Generates a CubemapData by rasterizing a direction-to-color function
 * across all 6 cubemap faces.
 *
 * Usage:
 *     $data = ProceduralCubemap::generate(512, function (Vec3 $dir): Color {
 *         $t = $dir->y * 0.5 + 0.5;
 *         return Color::lerp(new Color(0.8, 0.5, 0.2), new Color(0.1, 0.2, 0.6), $t);
 *     });
 */
class ProceduralCubemap
{
    /**
     * @param int $resolution Pixel width/height per face
     * @param callable(Vec3): Color $sampler Maps a normalized direction vector to a color
     */
    public static function generate(int $resolution, callable $sampler): CubemapData
    {
        $faces = [];

        // Face definitions: each face has a forward direction and up/right basis vectors.
        // Order: +X, -X, +Y, -Y, +Z, -Z (OpenGL cubemap standard)
        $faceBases = [
            // +X: look right
            [new Vec3(1, 0, 0),  new Vec3(0, 0, -1), new Vec3(0, -1, 0)],
            // -X: look left
            [new Vec3(-1, 0, 0), new Vec3(0, 0, 1),  new Vec3(0, -1, 0)],
            // +Y: look up
            [new Vec3(0, 1, 0),  new Vec3(1, 0, 0),  new Vec3(0, 0, 1)],
            // -Y: look down
            [new Vec3(0, -1, 0), new Vec3(1, 0, 0),  new Vec3(0, 0, -1)],
            // +Z: look forward
            [new Vec3(0, 0, 1),  new Vec3(1, 0, 0),  new Vec3(0, -1, 0)],
            // -Z: look back
            [new Vec3(0, 0, -1), new Vec3(-1, 0, 0), new Vec3(0, -1, 0)],
        ];

        $invRes = 1.0 / $resolution;

        foreach ($faceBases as [$forward, $right, $up]) {
            $pixels = [];

            for ($y = 0; $y < $resolution; $y++) {
                // Map pixel to [-1, 1] range (center of pixel)
                $v = 1.0 - (2.0 * ($y + 0.5) * $invRes);

                for ($x = 0; $x < $resolution; $x++) {
                    $u = 2.0 * ($x + 0.5) * $invRes - 1.0;

                    // Direction = forward + u * right + v * up, then normalize
                    $dx = $forward->x + $u * $right->x + $v * $up->x;
                    $dy = $forward->y + $u * $right->y + $v * $up->y;
                    $dz = $forward->z + $u * $right->z + $v * $up->z;

                    $len = sqrt($dx * $dx + $dy * $dy + $dz * $dz);
                    $dir = new Vec3($dx / $len, $dy / $len, $dz / $len);

                    $color = $sampler($dir);

                    $pixels[] = self::clampByte($color->r);
                    $pixels[] = self::clampByte($color->g);
                    $pixels[] = self::clampByte($color->b);
                    $pixels[] = self::clampByte($color->a);
                }
            }

            $faces[] = $pixels;
        }

        return new CubemapData($resolution, $faces);
    }

    private static function clampByte(float $v): int
    {
        return max(0, min(255, (int)($v * 255.0 + 0.5)));
    }
}
