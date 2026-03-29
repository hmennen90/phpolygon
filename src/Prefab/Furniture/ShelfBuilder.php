<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Furniture;

use PHPolygon\Component\BoxCollider3D;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\Transform3D;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

class ShelfBuilder
{
    private string $prefix = '';
    private float $width;
    private float $height;
    private float $depth;
    private int $shelves;
    private float $boardThickness;
    private float $sideThickness;

    private function __construct(float $width, float $height, float $depth, int $shelves)
    {
        $this->width = $width;
        $this->height = $height;
        $this->depth = $depth;
        $this->shelves = max(1, $shelves);
        $this->boardThickness = 0.02;
        $this->sideThickness = 0.03;
    }

    public static function standard(float $width = 0.8, float $height = 1.2, float $depth = 0.3, int $shelves = 3): self
    {
        return new self($width, $height, $depth, $shelves);
    }

    public function withPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function build(SceneBuilder $builder, Vec3 $position, Quaternion $rotation, FurnitureMaterials $materials): FurnitureResult
    {
        $names = [];
        $p = $this->prefix;
        $hw = $this->width * 0.5;
        $hh = $this->height * 0.5;

        // Side panels (left + right)
        foreach ([[-1, 'L'], [1, 'R']] as [$side, $suffix]) {
            $sidePos = $position->add($rotation->rotateVec3(new Vec3($side * ($hw - $this->sideThickness * 0.5), 0.0, 0.0)));
            $builder->entity("{$p}_ShelfSide{$suffix}")
                ->with(new Transform3D(
                    position: $sidePos,
                    rotation: $rotation,
                    scale: new Vec3($this->sideThickness * 0.5, $hh, $this->depth * 0.5),
                ))
                ->with(new MeshRenderer(meshId: 'box', materialId: $materials->primary))
                ->with(new BoxCollider3D(size: new Vec3(2.0, 2.0, 2.0), isStatic: true));
            $names[] = "{$p}_ShelfSide{$suffix}";
        }

        // Shelf boards (evenly spaced from bottom to top)
        $innerW = $this->width - $this->sideThickness * 2;
        for ($i = 0; $i <= $this->shelves; $i++) {
            $t = (float) $i / $this->shelves;
            $boardY = -$hh + $t * $this->height;
            $boardPos = $position->add($rotation->rotateVec3(new Vec3(0.0, $boardY, 0.0)));
            $builder->entity("{$p}_ShelfBoard_{$i}")
                ->with(new Transform3D(
                    position: $boardPos,
                    rotation: $rotation,
                    scale: new Vec3($innerW * 0.5, $this->boardThickness * 0.5, $this->depth * 0.5),
                ))
                ->with(new MeshRenderer(meshId: 'box', materialId: $materials->primary));
            $names[] = "{$p}_ShelfBoard_{$i}";
        }

        return new FurnitureResult(count($names), $names);
    }
}
