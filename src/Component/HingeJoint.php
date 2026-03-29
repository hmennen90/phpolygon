<?php

declare(strict_types=1);

namespace PHPolygon\Component;

use PHPolygon\ECS\AbstractComponent;
use PHPolygon\ECS\Attribute\Category;
use PHPolygon\ECS\Attribute\Hidden;
use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Serializable;
use PHPolygon\Math\Vec3;

/**
 * Hinge joint for doors, gates, lids, and other rotating objects.
 * The entity rotates around an anchor point along a specified axis.
 *
 * The anchor offset defines the hinge position relative to the entity center.
 * For a door hinged on its left edge: anchorOffset = (-doorWidth/2, 0, 0).
 */
#[Serializable]
#[Category('Physics')]
class HingeJoint extends AbstractComponent
{
    /** Hinge position relative to entity center (local space) */
    #[Property(editorHint: 'vec3')]
    public Vec3 $anchorOffset;

    /** Rotation axis (normalized, usually Y-up for doors) */
    #[Property(editorHint: 'vec3')]
    public Vec3 $axis;

    /** Current opening angle in radians (0 = closed) */
    #[Property]
    public float $angle;

    /** Minimum angle in radians */
    #[Property]
    public float $minAngle;

    /** Maximum angle in radians */
    #[Property]
    public float $maxAngle;

    /** Current angular velocity in radians/sec */
    #[Hidden]
    public float $angularVelocity = 0.0;

    /** Damping factor — higher = more friction (0 = no friction, 10 = heavy) */
    #[Property]
    public float $damping;

    /** Mass/inertia — higher = harder to push */
    #[Property]
    public float $mass;

    /** Base position of the entity when angle = 0 (set by DoorSystem on first frame) */
    #[Hidden]
    public ?Vec3 $basePosition = null;

    /** Base rotation of the entity when angle = 0 (set by DoorSystem on first frame) */
    #[Hidden]
    public ?\PHPolygon\Math\Quaternion $baseRotation = null;

    public function __construct(
        ?Vec3 $anchorOffset = null,
        ?Vec3 $axis = null,
        float $angle = 0.0,
        float $minAngle = 0.0,
        float $maxAngle = 1.8,
        float $damping = 3.0,
        float $mass = 5.0,
    ) {
        $this->anchorOffset = $anchorOffset ?? Vec3::zero();
        $this->axis = $axis ?? new Vec3(0.0, 1.0, 0.0);
        $this->angle = $angle;
        $this->minAngle = $minAngle;
        $this->maxAngle = $maxAngle;
        $this->damping = $damping;
        $this->mass = $mass;
    }

    /**
     * Apply an impulse (e.g., from player pushing).
     * Force is divided by mass for realistic inertia.
     */
    public function applyImpulse(float $torque): void
    {
        $this->angularVelocity += $torque / $this->mass;
    }
}
