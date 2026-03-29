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
 * Linear joint for sliding doors, drawers, elevators, etc.
 * The entity slides along a specified axis between min and max positions.
 */
#[Serializable]
#[Category('Physics')]
class LinearJoint extends AbstractComponent
{
    /** Slide direction (normalized, local space) */
    #[Property(editorHint: 'vec3')]
    public Vec3 $slideAxis;

    /** Current position along the axis (0 = closed) */
    #[Property]
    public float $position;

    #[Property]
    public float $minPosition;

    #[Property]
    public float $maxPosition;

    /** Current velocity along the axis */
    #[Hidden]
    public float $velocity = 0.0;

    #[Property]
    public float $damping;

    #[Property]
    public float $mass;

    /** Base position of the entity when position = 0 (captured on first frame) */
    #[Hidden]
    public ?Vec3 $basePosition = null;

    public function __construct(
        ?Vec3 $slideAxis = null,
        float $position = 0.0,
        float $minPosition = 0.0,
        float $maxPosition = 1.0,
        float $damping = 4.0,
        float $mass = 5.0,
    ) {
        $this->slideAxis = $slideAxis ?? new Vec3(1.0, 0.0, 0.0);
        $this->position = $position;
        $this->minPosition = $minPosition;
        $this->maxPosition = $maxPosition;
        $this->damping = $damping;
        $this->mass = $mass;
    }

    public function applyImpulse(float $force): void
    {
        $this->velocity += $force / $this->mass;
    }
}
