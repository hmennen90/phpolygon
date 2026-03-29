<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Door;

use PHPolygon\Component\HingeJoint;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

/**
 * Drehtuer — rotates around its center, no angle limits (360°).
 * Can have multiple segments (wings) for a revolving door effect.
 */
class RevolvingDoorBuilder extends AbstractDoorBuilder
{
    private int $segments;
    private float $damping;
    private float $mass;

    public function __construct(
        float $width = 0.8,
        float $height = 2.0,
        float $thickness = 0.04,
        int $segments = 4,
        float $damping = 1.5,
        float $mass = 15.0,
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->thickness = $thickness;
        $this->segments = max(1, $segments);
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
        $angleStep = M_PI / $this->segments;

        for ($i = 0; $i < $this->segments; $i++) {
            $segAngle = $i * $angleStep;
            $segRot = $rotation->multiply(Quaternion::fromAxisAngle(new Vec3(0.0, 1.0, 0.0), $segAngle));

            $entity = $this->buildPanel(
                $builder, "_RevDoor_{$i}", $position, $segRot,
                $this->width, $this->height, $this->thickness,
                $materials->panel,
            );

            // Only the first segment gets the hinge (all segments rotate together
            // because they share the same center position — DoorSystem rotates each)
            $entity->with(new HingeJoint(
                anchorOffset: Vec3::zero(), // hinge at center
                axis: new Vec3(0.0, 1.0, 0.0),
                angle: $segAngle,
                minAngle: -1000.0, // no limits (full rotation)
                maxAngle: 1000.0,
                damping: $this->damping,
                mass: $this->mass,
            ));
            $names[] = $this->prefix . "_RevDoor_{$i}";
        }

        return new DoorResult($this->segments, $names);
    }
}
