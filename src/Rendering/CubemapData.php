<?php

declare(strict_types=1);

namespace PHPolygon\Rendering;

/**
 * Raw pixel data for a procedurally generated cubemap.
 * Each face is a flat array of RGBA bytes (4 bytes per pixel, row-major).
 * Face order: +X (right), -X (left), +Y (top), -Y (bottom), +Z (front), -Z (back).
 */
readonly class CubemapData
{
    /**
     * @param int $resolution Width and height of each face in pixels
     * @param list<int[]> $faces 6 face arrays, each containing resolution*resolution*4 RGBA bytes (0-255)
     */
    public function __construct(
        public int $resolution,
        public array $faces,
    ) {}
}
