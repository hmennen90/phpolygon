<?php

declare(strict_types=1);

namespace PHPolygon\Component;

use PHPolygon\ECS\AbstractComponent;
use PHPolygon\ECS\Attribute\Category;
use PHPolygon\ECS\Attribute\Hidden;
use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Serializable;
use PHPolygon\Math\Vec3;

#[Serializable]
#[Category('Physics')]
class CharacterController3D extends AbstractComponent
{
    #[Property]
    public float $height;

    #[Property]
    public float $radius;

    #[Property]
    public float $stepHeight;

    #[Property]
    public float $slopeLimit;

    #[Hidden]
    public Vec3 $velocity;

    #[Hidden]
    public bool $isGrounded;

    public function __construct(
        float $height = 1.8,
        float $radius = 0.4,
        float $stepHeight = 0.3,
        float $slopeLimit = 45.0,
    ) {
        $this->height = $height;
        $this->radius = $radius;
        $this->stepHeight = $stepHeight;
        $this->slopeLimit = $slopeLimit;
        $this->velocity = Vec3::zero();
        $this->isGrounded = false;
    }
}
