<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Door;

use PHPolygon\Component\HingeJoint;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

/**
 * Doppeltuer — two panels with opposing hinges (saloon, entrance).
 */
class DoubleDoorBuilder extends AbstractDoorBuilder
{
    private float $maxAngle;
    private float $damping;
    private float $mass;

    public function __construct(
        float $width = 1.6,
        float $height = 2.0,
        float $thickness = 0.04,
        float $maxAngle = 1.6,
        float $damping = 2.5,
        float $mass = 6.0,
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->thickness = $thickness;
        $this->maxAngle = $maxAngle;
        $this->damping = $damping;
        $this->mass = $mass;
    }

    public function build(
        SceneBuilder $builder,
        Vec3 $position,
        Quaternion $rotation,
        DoorMaterials $materials,
    ): DoorResult {
        $names = [];
        $entityCount = 0;
        $panelW = $this->width * 0.5;
        $halfW = $this->width * 0.25;

        if ($this->hasFrame) {
            $entityCount += $this->buildFrame(
                $builder, $position, $rotation,
                $this->width, $this->height,
                $materials->frame,
            );
            $names = [$this->prefix . '_FrameL', $this->prefix . '_FrameR', $this->prefix . '_FrameT'];
        }

        // Left panel — hinged on left edge
        $leftPos = $this->offsetPoint($position, $rotation, new Vec3(-$halfW, 0.0, 0.0));
        $leftEntity = $this->buildPanel(
            $builder, '_DoorL', $leftPos, $rotation,
            $panelW, $this->height, $this->thickness,
            $materials->panel,
        );
        $leftEntity->with(new HingeJoint(
            anchorOffset: new Vec3(-$panelW * 0.5, 0.0, 0.0),
            axis: new Vec3(0.0, 1.0, 0.0),
            angle: 0.0, minAngle: -0.1, maxAngle: $this->maxAngle,
            damping: $this->damping, mass: $this->mass,
        ));
        $names[] = $this->prefix . '_DoorL';
        $entityCount++;

        // Right panel — hinged on right edge
        $rightPos = $this->offsetPoint($position, $rotation, new Vec3($halfW, 0.0, 0.0));
        $rightEntity = $this->buildPanel(
            $builder, '_DoorR', $rightPos, $rotation,
            $panelW, $this->height, $this->thickness,
            $materials->panel,
        );
        $rightEntity->with(new HingeJoint(
            anchorOffset: new Vec3($panelW * 0.5, 0.0, 0.0),
            axis: new Vec3(0.0, 1.0, 0.0),
            angle: 0.0, minAngle: -$this->maxAngle, maxAngle: 0.1,
            damping: $this->damping, mass: $this->mass,
        ));
        $names[] = $this->prefix . '_DoorR';
        $entityCount++;

        return new DoorResult($entityCount, $names);
    }
}
