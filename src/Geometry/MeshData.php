<?php

declare(strict_types=1);

namespace PHPolygon\Geometry;

readonly class MeshData
{
    /**
     * @param float[] $vertices Flat array: x,y,z per vertex
     * @param float[] $normals  Flat array: nx,ny,nz per vertex
     * @param float[] $uvs      Flat array: u,v per vertex
     * @param int[]   $indices  Triangle list, 3 ints per triangle
     */
    public function __construct(
        public array $vertices,
        public array $normals,
        public array $uvs,
        public array $indices,
    ) {}

    public function vertexCount(): int
    {
        return (int)(count($this->vertices) / 3);
    }

    public function triangleCount(): int
    {
        return (int)(count($this->indices) / 3);
    }
}
