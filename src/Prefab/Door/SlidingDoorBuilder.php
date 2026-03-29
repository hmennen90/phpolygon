<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Door;

use PHPolygon\Component\LinearJoint;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

/**
 * Schiebetuer — slides along a rail (barn, modern, Japanese).
 */
class SlidingDoorBuilder extends AbstractDoorBuilder
{
    private float $slideDistance;
    private string $slideDirection;
    private float $damping;
    private float $mass;

    public function __construct(
        float $width = 1.2,
        float $height = 2.0,
        float $thickness = 0.04,
        float $slideDistance = 0.0,
        string $slideDirection = 'right',
        float $damping = 4.0,
        float $mass = 10.0,
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->thickness = $thickness;
        $this->slideDistance = $slideDistance > 0 ? $slideDistance : $width + 0.1;
        $this->slideDirection = $slideDirection;
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

        if ($this->hasFrame) {
            $entityCount += $this->buildFrame(
                $builder, $position, $rotation,
                $this->width, $this->height, $materials->frame,
            );
            $names = [$this->prefix . '_FrameL', $this->prefix . '_FrameR', $this->prefix . '_FrameT'];
        }

        $slideX = $this->slideDirection === 'right' ? 1.0 : -1.0;

        $entity = $this->buildPanel(
            $builder, '_Door', $position, $rotation,
            $this->width, $this->height, $this->thickness,
            $materials->panel,
        );
        $entity->with(new LinearJoint(
            slideAxis: new Vec3($slideX, 0.0, 0.0),
            position: 0.0,
            minPosition: 0.0,
            maxPosition: $this->slideDistance,
            damping: $this->damping,
            mass: $this->mass,
        ));
        $names[] = $this->prefix . '_Door';
        $entityCount++;

        return new DoorResult($entityCount, $names);
    }
}
