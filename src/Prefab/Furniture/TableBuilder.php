<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Furniture;

use PHPolygon\Component\BoxCollider3D;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\Transform3D;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

class TableBuilder
{
    private string $prefix = '';
    private string $shape;
    private float $width;
    private float $depth;
    private float $height;
    private float $legThickness;
    private float $topThickness;

    private function __construct(string $shape, float $width, float $depth, float $height)
    {
        $this->shape = $shape;
        $this->width = $width;
        $this->depth = $depth;
        $this->height = $height;
        $this->legThickness = 0.04;
        $this->topThickness = 0.04;
    }

    public static function rectangular(float $width = 1.0, float $depth = 0.7, float $height = 0.75): self
    {
        return new self('rect', $width, $depth, $height);
    }

    public static function round(float $radius = 0.4, float $height = 0.75): self
    {
        return new self('round', $radius * 2, $radius * 2, $height);
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

        $topY = $position->y + $this->height * 0.5;
        $topMesh = $this->shape === 'round' ? 'cylinder' : 'box';

        // Table top
        $builder->entity($p . '_TableTop')
            ->with(new Transform3D(
                position: new Vec3($position->x, $topY, $position->z),
                rotation: $rotation,
                scale: new Vec3($this->width * 0.5, $this->topThickness * 0.5, $this->depth * 0.5),
            ))
            ->with(new MeshRenderer(meshId: $topMesh, materialId: $materials->primary))
            ->with(new BoxCollider3D(size: new Vec3(2.0, 2.0, 2.0), isStatic: true));
        $names[] = $p . '_TableTop';

        if ($this->shape === 'round') {
            // Single center pedestal
            $builder->entity($p . '_TableLeg')
                ->with(new Transform3D(
                    position: new Vec3($position->x, $position->y + $this->height * 0.25, $position->z),
                    rotation: $rotation,
                    scale: new Vec3($this->legThickness, $this->height * 0.25, $this->legThickness),
                ))
                ->with(new MeshRenderer(meshId: 'cylinder', materialId: $materials->secondary));
            $names[] = $p . '_TableLeg';
        } else {
            // 4 legs at corners
            $hx = ($this->width * 0.5 - $this->legThickness) * 0.9;
            $hz = ($this->depth * 0.5 - $this->legThickness) * 0.9;
            $legY = $position->y + $this->height * 0.25;
            $legH = ($this->height - $this->topThickness) * 0.5;

            $offsets = [[-$hx, -$hz], [$hx, -$hz], [-$hx, $hz], [$hx, $hz]];
            foreach ($offsets as $i => [$ox, $oz]) {
                $legPos = $position->add($rotation->rotateVec3(new Vec3($ox, $legY - $position->y, $oz)));
                $builder->entity("{$p}_TableLeg_{$i}")
                    ->with(new Transform3D(
                        position: $legPos,
                        rotation: $rotation,
                        scale: new Vec3($this->legThickness * 0.5, $legH, $this->legThickness * 0.5),
                    ))
                    ->with(new MeshRenderer(meshId: 'cylinder', materialId: $materials->secondary));
                $names[] = "{$p}_TableLeg_{$i}";
            }
        }

        return new FurnitureResult(count($names), $names);
    }
}
