<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Door;

use PHPolygon\Component\HingeJoint;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

/**
 * Einzeltuer — single panel with hinge on left or right edge.
 * With frame: hinge attaches to frame post, door is inset.
 * Without frame: hinge attaches directly at door edge (wall mount).
 */
class SingleDoorBuilder extends AbstractDoorBuilder
{
    private string $hingeSide;
    private float $maxAngle;
    private float $damping;
    private float $mass;
    private float $initialAngle;

    public function __construct(
        float $width = 0.8,
        float $height = 1.8,
        float $thickness = 0.04,
        string $hingeSide = 'left',
        float $maxAngle = 1.8,
        float $damping = 2.5,
        float $mass = 8.0,
        float $initialAngle = 0.0,
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->thickness = $thickness;
        $this->hingeSide = $hingeSide;
        $this->maxAngle = $maxAngle;
        $this->damping = $damping;
        $this->mass = $mass;
        $this->initialAngle = $initialAngle;
    }

    public function build(
        SceneBuilder $builder,
        Vec3 $position,
        Quaternion $rotation,
        DoorMaterials $materials,
    ): DoorResult {
        $names = [];
        $entityCount = 0;

        // Build frame if enabled
        if ($this->hasFrame) {
            $entityCount += $this->buildFrame(
                $builder, $position, $rotation,
                $this->width, $this->height,
                $materials->frame,
            );
            $names[] = $this->prefix . '_FrameL';
            $names[] = $this->prefix . '_FrameR';
            $names[] = $this->prefix . '_FrameT';
        }

        // Hinge position: at door edge (no frame) or at frame inner edge (with frame)
        $hingeX = $this->hingeSide === 'left' ? -$this->width * 0.5 : $this->width * 0.5;
        $minAngle = $this->hingeSide === 'left' ? -0.1 : -$this->maxAngle;
        $maxAngle = $this->hingeSide === 'left' ? $this->maxAngle : 0.1;

        $entity = $this->buildPanel(
            $builder, '_Door', $position, $rotation,
            $this->width, $this->height, $this->thickness,
            $materials->panel,
        );
        $entity->with(new HingeJoint(
            anchorOffset: new Vec3($hingeX, 0.0, 0.0),
            axis: new Vec3(0.0, 1.0, 0.0),
            angle: $this->initialAngle,
            minAngle: $minAngle,
            maxAngle: $maxAngle,
            damping: $this->damping,
            mass: $this->mass,
        ));
        $names[] = $this->prefix . '_Door';
        $entityCount++;

        return new DoorResult($entityCount, $names);
    }
}
