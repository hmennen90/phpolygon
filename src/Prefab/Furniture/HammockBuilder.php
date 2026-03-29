<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Furniture;

use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\Transform3D;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

class HammockBuilder
{
    private string $prefix = '';
    private float $length;
    private float $postHeight;
    private float $sagAmount;

    private function __construct(float $length, float $postHeight, float $sagAmount)
    {
        $this->length = $length;
        $this->postHeight = $postHeight;
        $this->sagAmount = $sagAmount;
    }

    public static function standard(float $length = 1.6, float $postHeight = 1.2, float $sagAmount = 0.3): self
    {
        return new self($length, $postHeight, $sagAmount);
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
        $halfLen = $this->length * 0.5;
        $bodyY = $this->postHeight - $this->sagAmount;
        $bodyHalfLen = $halfLen * 0.7;

        // Two posts
        foreach ([-1, 1] as $i => $side) {
            $postPos = $position->add($rotation->rotateVec3(new Vec3(0.0, $this->postHeight * 0.5, $side * $halfLen)));
            $builder->entity("{$p}_HammockPost_{$i}")
                ->with(new Transform3D(
                    position: $postPos,
                    rotation: $rotation,
                    scale: new Vec3(0.03, $this->postHeight * 0.5, 0.03),
                ))
                ->with(new MeshRenderer(meshId: 'cylinder', materialId: $materials->secondary));
            $names[] = "{$p}_HammockPost_{$i}";
        }

        // Fabric body (sagging box between posts)
        $bodyPos = $position->add($rotation->rotateVec3(new Vec3(0.0, $bodyY, 0.0)));
        $builder->entity("{$p}_HammockBody")
            ->with(new Transform3D(
                position: $bodyPos,
                rotation: $rotation,
                scale: new Vec3(0.25, 0.06, $bodyHalfLen),
            ))
            ->with(new MeshRenderer(meshId: 'box', materialId: $materials->fabric));
        $names[] = "{$p}_HammockBody";

        // Rope ties — diagonal from post top to fabric edge.
        // Each rope connects: postTop (0, postHeight, ±halfLen) → fabricEdge (0, bodyY, ±bodyHalfLen)
        foreach ([-1, 1] as $i => $side) {
            $postTopZ = $side * $halfLen;
            $fabricEdgeZ = $side * $bodyHalfLen;

            // Rope midpoint
            $midY = ($this->postHeight + $bodyY) * 0.5;
            $midZ = ($postTopZ + $fabricEdgeZ) * 0.5;

            // Rope length = distance between post top and fabric edge
            $dz = $postTopZ - $fabricEdgeZ;
            $dy = $this->postHeight - $bodyY;
            $ropeLen = sqrt($dy * $dy + $dz * $dz);

            // Tilt angle: rope runs from top-outside to bottom-inside
            $tiltAngle = atan2($dz, $dy) * $side;

            $ropePos = $position->add($rotation->rotateVec3(new Vec3(0.0, $midY, $midZ)));
            $ropeTilt = Quaternion::fromAxisAngle(new Vec3(1.0, 0.0, 0.0), $tiltAngle);
            $ropeRot = $rotation->multiply($ropeTilt);

            $builder->entity("{$p}_HammockRope_{$i}")
                ->with(new Transform3D(
                    position: $ropePos,
                    rotation: $ropeRot,
                    scale: new Vec3(0.01, $ropeLen * 0.5, 0.01),
                ))
                ->with(new MeshRenderer(meshId: 'cylinder', materialId: $materials->fabric));
            $names[] = "{$p}_HammockRope_{$i}";
        }

        return new FurnitureResult(count($names), $names);
    }
}
