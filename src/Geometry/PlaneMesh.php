<?php

declare(strict_types=1);

namespace PHPolygon\Geometry;

/**
 * Generates a single quad in the XZ plane, centered at the origin.
 * Normal points upward (0, 1, 0).
 * 4 vertices, 6 indices (2 triangles).
 */
class PlaneMesh
{
    public static function generate(float $width, float $depth): MeshData
    {
        $hw = $width / 2.0;
        $hd = $depth / 2.0;

        $vertices = [
            -$hw, 0.0, -$hd,
             $hw, 0.0, -$hd,
             $hw, 0.0,  $hd,
            -$hw, 0.0,  $hd,
        ];

        $normals = [
            0.0, 1.0, 0.0,
            0.0, 1.0, 0.0,
            0.0, 1.0, 0.0,
            0.0, 1.0, 0.0,
        ];

        $uvs = [
            0.0, 0.0,
            1.0, 0.0,
            1.0, 1.0,
            0.0, 1.0,
        ];

        $indices = [0, 1, 2, 0, 2, 3];

        return new MeshData($vertices, $normals, $uvs, $indices);
    }
}
