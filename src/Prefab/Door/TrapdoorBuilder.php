<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Door;

use PHPolygon\Component\HingeJoint;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

/**
 * Falltuer/Klappe — horizontal panel that opens up/down.
 * Hinged on one edge, rotates around a horizontal axis (X or Z).
 */
class TrapdoorBuilder extends AbstractDoorBuilder
{
    private float $depth;
    private float $openAngle;
    private string $hingeSide;
    private float $damping;
    private float $mass;

    public function __construct(
        float $width = 0.8,
        float $depth = 0.8,
        float $thickness = 0.04,
        float $openAngle = 1.5,
        string $hingeSide = 'back',
        float $damping = 3.0,
        float $mass = 10.0,
    ) {
        $this->width = $width;
        $this->height = $thickness; // trapdoor is horizontal, "height" = thickness
        $this->depth = $depth;
        $this->thickness = $thickness;
        $this->openAngle = $openAngle;
        $this->hingeSide = $hingeSide;
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

        // Trapdoor frame is horizontal — uses width × depth for the opening
        if ($this->hasFrame) {
            $entityCount += $this->buildFrame(
                $builder, $position, $rotation,
                $this->width, $this->depth, $materials->frame,
            );
            $names = [$this->prefix . '_FrameL', $this->prefix . '_FrameR', $this->prefix . '_FrameT'];
        }

        // Trapdoor is a flat panel (width × depth × thickness)
        $hingeOffset = match ($this->hingeSide) {
            'back' => new Vec3(0.0, 0.0, -$this->depth * 0.5),
            'front' => new Vec3(0.0, 0.0, $this->depth * 0.5),
            'left' => new Vec3(-$this->width * 0.5, 0.0, 0.0),
            'right' => new Vec3($this->width * 0.5, 0.0, 0.0),
            default => new Vec3(0.0, 0.0, -$this->depth * 0.5),
        };

        $hingeAxis = match ($this->hingeSide) {
            'back', 'front' => new Vec3(1.0, 0.0, 0.0),
            'left', 'right' => new Vec3(0.0, 0.0, 1.0),
            default => new Vec3(1.0, 0.0, 0.0),
        };

        $entity = $this->buildPanel(
            $builder, '_Trapdoor', $position, $rotation,
            $this->width, $this->thickness, $this->depth,
            $materials->panel,
        );
        $entity->with(new HingeJoint(
            anchorOffset: $hingeOffset,
            axis: $hingeAxis,
            angle: 0.0,
            minAngle: 0.0,
            maxAngle: $this->openAngle,
            damping: $this->damping,
            mass: $this->mass,
        ));

        $names[] = $this->prefix . '_Trapdoor';
        $entityCount++;

        return new DoorResult($entityCount, $names);
    }
}
