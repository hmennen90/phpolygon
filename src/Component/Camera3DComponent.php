<?php

declare(strict_types=1);

namespace PHPolygon\Component;

use PHPolygon\ECS\AbstractComponent;
use PHPolygon\ECS\Attribute\Category;
use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Range;
use PHPolygon\ECS\Attribute\Serializable;
use PHPolygon\Math\Vec3;

enum ProjectionType
{
    case Perspective;
    case Orthographic;
}

#[Serializable]
#[Category('Rendering')]
class Camera3DComponent extends AbstractComponent
{
    #[Property(editorHint: 'slider')]
    #[Range(min: 1, max: 179)]
    public float $fov;

    #[Property]
    public float $near;

    #[Property]
    public float $far;

    #[Property]
    public ProjectionType $projectionType;

    #[Property]
    public bool $active;

    /**
     * Eye/viewpoint offset applied to the camera entity's world position at
     * render time. Lets the physics capsule be centred on the body while the
     * camera sits at eye height. The offset is applied in world space (after
     * the transform position is resolved) so it stays stable when the body
     * leans or the capsule rotates.
     */
    #[Property(editorHint: 'vec3')]
    public Vec3 $eyeOffset;

    public function __construct(
        float $fov = 60.0,
        float $near = 0.1,
        float $far = 1000.0,
        ProjectionType $projectionType = ProjectionType::Perspective,
        bool $active = true,
        ?Vec3 $eyeOffset = null,
    ) {
        $this->fov = $fov;
        $this->near = $near;
        $this->far = $far;
        $this->projectionType = $projectionType;
        $this->active = $active;
        $this->eyeOffset = $eyeOffset ?? Vec3::zero();
    }
}
