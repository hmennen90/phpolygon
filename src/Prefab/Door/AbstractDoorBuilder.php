<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Door;

use PHPolygon\Component\BoxCollider3D;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\Transform3D;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

abstract class AbstractDoorBuilder
{
    protected float $width;
    protected float $height;
    protected float $thickness;
    protected bool $hasFrame = false;
    protected float $frameWidth = 0.08;
    protected float $frameDepth = 0.10;
    protected string $prefix = '';

    public function withPrefix(string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Enable door frame / Zarge.
     * When enabled, the frame is built around the door opening and the hinge
     * attaches to the frame instead of the wall.
     *
     * @param float $frameWidth  Width of frame beams (default 8cm)
     * @param float $frameDepth  Depth of frame beams (default 10cm, matches wall thickness)
     */
    public function withFrame(float $frameWidth = 0.08, float $frameDepth = 0.10): static
    {
        $this->hasFrame = true;
        $this->frameWidth = $frameWidth;
        $this->frameDepth = $frameDepth;
        return $this;
    }

    abstract public function build(
        SceneBuilder $builder,
        Vec3 $position,
        Quaternion $rotation,
        DoorMaterials $materials,
    ): DoorResult;

    /**
     * Create a door panel entity with mesh, collider, and transform.
     */
    /**
     * Create a door panel entity with mesh, collider, and transform.
     * Door panels are static colliders — their movement is managed by DoorSystem,
     * not by Physics3DSystem. The collider updates automatically because
     * Physics3DSystem reads the world matrix each frame.
     */
    protected function buildPanel(
        SceneBuilder $builder,
        string $name,
        Vec3 $position,
        Quaternion $rotation,
        float $width,
        float $height,
        float $thickness,
        string $materialId,
        bool $isStatic = true,
    ): \PHPolygon\Scene\EntityDeclaration {
        return $builder->entity($this->prefix . $name)
            ->with(new Transform3D(
                position: $position,
                rotation: $rotation,
                scale: new Vec3($width * 0.5, $height * 0.5, $thickness * 0.5),
            ))
            ->with(new MeshRenderer(meshId: 'box', materialId: $materialId))
            ->with(new BoxCollider3D(size: new Vec3(2.0, 2.0, 2.0), isStatic: $isStatic));
    }

    /**
     * Build a door frame (Zarge) — three beams around the opening (left, right, top).
     * The frame insets the door opening by frameWidth on each side.
     *
     * @return int Number of entities created (3: left post, right post, top beam)
     */
    protected function buildFrame(
        SceneBuilder $builder,
        Vec3 $position,
        Quaternion $rotation,
        float $openingWidth,
        float $openingHeight,
        string $materialId,
    ): int {
        $hw = $openingWidth * 0.5;
        $fW = $this->frameWidth;
        $fD = $this->frameDepth;

        // Left post — full height, on the left edge of the opening
        $leftPos = $this->offsetPoint($position, $rotation, new Vec3(-$hw - $fW * 0.5, 0.0, 0.0));
        $builder->entity($this->prefix . '_FrameL')
            ->with(new Transform3D(
                position: $leftPos,
                rotation: $rotation,
                scale: new Vec3($fW * 0.5, $openingHeight * 0.5, $fD * 0.5),
            ))
            ->with(new MeshRenderer(meshId: 'box', materialId: $materialId))
            ->with(new BoxCollider3D(size: new Vec3(2.0, 2.0, 2.0), isStatic: true));

        // Right post
        $rightPos = $this->offsetPoint($position, $rotation, new Vec3($hw + $fW * 0.5, 0.0, 0.0));
        $builder->entity($this->prefix . '_FrameR')
            ->with(new Transform3D(
                position: $rightPos,
                rotation: $rotation,
                scale: new Vec3($fW * 0.5, $openingHeight * 0.5, $fD * 0.5),
            ))
            ->with(new MeshRenderer(meshId: 'box', materialId: $materialId))
            ->with(new BoxCollider3D(size: new Vec3(2.0, 2.0, 2.0), isStatic: true));

        // Top beam — visual only (no collider, so player can walk under it)
        $totalW = $openingWidth + $fW * 2;
        $topPos = $this->offsetPoint($position, $rotation, new Vec3(0.0, $openingHeight * 0.5 + $fW * 0.5, 0.0));
        $builder->entity($this->prefix . '_FrameT')
            ->with(new Transform3D(
                position: $topPos,
                rotation: $rotation,
                scale: new Vec3($totalW * 0.5, $fW * 0.5, $fD * 0.5),
            ))
            ->with(new MeshRenderer(meshId: 'box', materialId: $materialId));

        return 3;
    }

    /**
     * Get the hinge X offset accounting for frame.
     * Without frame: hinge at door edge. With frame: hinge at frame inner edge.
     */
    protected function getHingeX(string $hingeSide): float
    {
        $doorHalfW = $this->width * 0.5;
        if ($this->hasFrame) {
            // Hinge sits at the inner edge of the frame post
            $frameInner = $doorHalfW + $this->frameWidth;
            return $hingeSide === 'left' ? -$frameInner : $frameInner;
        }
        return $hingeSide === 'left' ? -$doorHalfW : $doorHalfW;
    }

    protected function offsetPoint(Vec3 $base, Quaternion $rotation, Vec3 $localOffset): Vec3
    {
        return $base->add($rotation->rotateVec3($localOffset));
    }
}
