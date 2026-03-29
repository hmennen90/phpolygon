<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Furniture;

use PHPolygon\Component\BoxCollider3D;
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
    private float $hangHeight;

    private function __construct(float $length, float $postHeight, float $hangHeight)
    {
        $this->length = $length;
        $this->postHeight = $postHeight;
        $this->hangHeight = $hangHeight;
    }

    /**
     * @param float $length     Distance between posts
     * @param float $postHeight Post height from ground
     * @param float $hangHeight Height of fabric center from ground (lower = more sag)
     */
    public static function standard(float $length = 1.6, float $postHeight = 0.9, float $hangHeight = 0.45): self
    {
        return new self($length, $postHeight, $hangHeight);
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
        $bodyHalfLen = $halfLen * 0.7;

        // Two posts (from ground to postHeight)
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

        // Fabric body — hangs at hangHeight, with collider so player bumps into it
        $bodyPos = $position->add($rotation->rotateVec3(new Vec3(0.0, $this->hangHeight, 0.0)));
        $builder->entity("{$p}_HammockBody")
            ->with(new Transform3D(
                position: $bodyPos,
                rotation: $rotation,
                scale: new Vec3(0.25, 0.06, $bodyHalfLen),
            ))
            ->with(new MeshRenderer(meshId: 'box', materialId: $materials->fabric))
            ->with(new BoxCollider3D(size: new Vec3(2.0, 2.0, 2.0), isStatic: true));
        $names[] = "{$p}_HammockBody";

        // Rope ties — diagonal from post top to fabric edge
        foreach ([-1, 1] as $i => $side) {
            // Post top position (relative to $position)
            $postTopY = $this->postHeight;
            $postTopZ = $side * $halfLen;

            // Fabric edge position
            $fabricY = $this->hangHeight;
            $fabricZ = $side * $bodyHalfLen;

            // Midpoint
            $midY = ($postTopY + $fabricY) * 0.5;
            $midZ = ($postTopZ + $fabricZ) * 0.5;

            // Rope length
            $dy = $postTopY - $fabricY;
            $dz = $postTopZ - $fabricZ;
            $ropeLen = sqrt($dy * $dy + $dz * $dz);

            // Tilt angle from vertical
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
