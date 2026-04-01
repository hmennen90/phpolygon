<?php

declare(strict_types=1);

namespace PHPolygon\Component;

use PHPolygon\ECS\AbstractComponent;
use PHPolygon\ECS\Attribute\Category;
use PHPolygon\ECS\Attribute\Hidden;
use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Serializable;

/**
 * Heightmap-based terrain collider. O(1) height query via bilinear interpolation.
 *
 * Faster than MeshCollider3D for large terrain surfaces because it avoids BVH
 * triangle traversal. Suitable for any surface that can be expressed as a 2D
 * height function f(x, z) → y.
 *
 * Usage:
 *   $hm = new HeightmapCollider3D(gridWidth: 128, gridDepth: 128, ...);
 *   $hm->populateFromFunction(fn($x, $z) => terrainHeight($x, $z));
 *   // Then Physics3DSystem can call $hm->getHeightAt($x, $z) for any world position.
 */
#[Serializable]
#[Category('Physics')]
class HeightmapCollider3D extends AbstractComponent
{
    /** Number of sample columns (X axis). */
    #[Property]
    public int $gridWidth;

    /** Number of sample rows (Z axis). */
    #[Property]
    public int $gridDepth;

    /** World-space X coordinate of grid column 0. */
    #[Property]
    public float $worldMinX;

    /** World-space X coordinate of grid column (gridWidth - 1). */
    #[Property]
    public float $worldMaxX;

    /** World-space Z coordinate of grid row 0. */
    #[Property]
    public float $worldMinZ;

    /** World-space Z coordinate of grid row (gridDepth - 1). */
    #[Property]
    public float $worldMaxZ;

    /**
     * Flat height data: heightData[z * gridWidth + x] = world Y at grid cell (x, z).
     * Populated by populateFromFunction(). Not serialised (regenerated at load time).
     *
     * @var float[]
     */
    #[Hidden]
    private array $heightData = [];

    public function __construct(
        int   $gridWidth   = 64,
        int   $gridDepth   = 64,
        float $worldMinX   = -50.0,
        float $worldMaxX   =  50.0,
        float $worldMinZ   = -50.0,
        float $worldMaxZ   =  50.0,
    ) {
        $this->gridWidth  = max(2, $gridWidth);
        $this->gridDepth  = max(2, $gridDepth);
        $this->worldMinX  = $worldMinX;
        $this->worldMaxX  = $worldMaxX;
        $this->worldMinZ  = $worldMinZ;
        $this->worldMaxZ  = $worldMaxZ;
    }

    /**
     * Populate the heightmap by calling $fn(float $worldX, float $worldZ): float
     * for every grid cell. Call once after construction (or after deserialisation).
     *
     * @param callable(float, float): float $fn
     */
    public function populateFromFunction(callable $fn): void
    {
        $this->heightData = [];

        $xRange = $this->worldMaxX - $this->worldMinX;
        $zRange = $this->worldMaxZ - $this->worldMinZ;

        for ($zi = 0; $zi < $this->gridDepth; $zi++) {
            $wz = $this->worldMinZ + ($zi / ($this->gridDepth - 1)) * $zRange;
            for ($xi = 0; $xi < $this->gridWidth; $xi++) {
                $wx = $this->worldMinX + ($xi / ($this->gridWidth - 1)) * $xRange;
                $this->heightData[$zi * $this->gridWidth + $xi] = (float) $fn($wx, $wz);
            }
        }
    }

    /**
     * Returns the interpolated terrain height (world Y) at the given world (X, Z).
     * Uses bilinear interpolation between the four nearest grid samples.
     * Returns 0.0 if the heightmap has not been populated yet.
     */
    public function getHeightAt(float $worldX, float $worldZ): float
    {
        if (empty($this->heightData)) {
            return 0.0;
        }

        $xRange = $this->worldMaxX - $this->worldMinX;
        $zRange = $this->worldMaxZ - $this->worldMinZ;

        if ($xRange <= 0.0 || $zRange <= 0.0) {
            return 0.0;
        }

        // Normalised grid coordinates [0, gridWidth-1] and [0, gridDepth-1]
        $gx = max(0.0, min((float)($this->gridWidth - 1), ($worldX - $this->worldMinX) / $xRange * ($this->gridWidth - 1)));
        $gz = max(0.0, min((float)($this->gridDepth - 1), ($worldZ - $this->worldMinZ) / $zRange * ($this->gridDepth - 1)));

        $x0 = (int) $gx;
        $z0 = (int) $gz;
        $x1 = min($x0 + 1, $this->gridWidth - 1);
        $z1 = min($z0 + 1, $this->gridDepth - 1);

        $fx = $gx - $x0;
        $fz = $gz - $z0;

        $h00 = $this->heightData[$z0 * $this->gridWidth + $x0] ?? 0.0;
        $h10 = $this->heightData[$z0 * $this->gridWidth + $x1] ?? 0.0;
        $h01 = $this->heightData[$z1 * $this->gridWidth + $x0] ?? 0.0;
        $h11 = $this->heightData[$z1 * $this->gridWidth + $x1] ?? 0.0;

        return $h00 * (1.0 - $fx) * (1.0 - $fz)
             + $h10 * $fx          * (1.0 - $fz)
             + $h01 * (1.0 - $fx) * $fz
             + $h11 * $fx          * $fz;
    }

    /** Whether the heightmap has been populated. */
    public function isPopulated(): bool
    {
        return !empty($this->heightData);
    }
}
