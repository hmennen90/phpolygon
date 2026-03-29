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
class RigidBody3D extends AbstractComponent
{
    #[Property]
    public BodyType $bodyType;

    #[Property]
    public float $mass;

    #[Hidden]
    public Vec3 $velocity;

    #[Property]
    public float $gravityScale;

    #[Property]
    public float $linearDamping;

    /** Bounciness: 0 = no bounce, 1 = perfect elastic */
    #[Property]
    public float $restitution;

    /** Surface friction: 0 = ice, 1 = rubber */
    #[Property]
    public float $friction;

    /** When true, body translates but never rotates (big performance win) */
    #[Property]
    public bool $fixedRotation;

    // --- Sleep state ---

    #[Hidden]
    public bool $isSleeping = false;

    #[Hidden]
    public int $sleepCounter = 0;

    /** Previous frame position for kinematic velocity computation */
    #[Hidden]
    public ?Vec3 $previousPosition = null;

    private const SLEEP_THRESHOLD = 0.01;
    private const SLEEP_FRAMES = 60;

    public function __construct(
        BodyType $bodyType = BodyType::Dynamic,
        float $mass = 1.0,
        float $gravityScale = 1.0,
        float $linearDamping = 0.05,
        float $restitution = 0.2,
        float $friction = 0.4,
        bool $fixedRotation = true,
    ) {
        $this->bodyType = $bodyType;
        $this->mass = max(0.001, $mass);
        $this->gravityScale = $gravityScale;
        $this->linearDamping = $linearDamping;
        $this->restitution = $restitution;
        $this->friction = $friction;
        $this->fixedRotation = $fixedRotation;
        $this->velocity = Vec3::zero();
    }

    public function getInverseMass(): float
    {
        if ($this->bodyType !== BodyType::Dynamic) {
            return 0.0; // Infinite mass for static/kinematic
        }
        return 1.0 / $this->mass;
    }

    public function addImpulse(Vec3 $impulse): void
    {
        if ($this->bodyType !== BodyType::Dynamic) {
            return;
        }
        $invMass = $this->getInverseMass();
        $this->velocity = $this->velocity->add($impulse->mul($invMass));
        $this->wake();
    }

    public function wake(): void
    {
        $this->isSleeping = false;
        $this->sleepCounter = 0;
    }

    public function updateSleep(): void
    {
        if ($this->bodyType !== BodyType::Dynamic) {
            return;
        }

        if ($this->velocity->lengthSquared() < self::SLEEP_THRESHOLD * self::SLEEP_THRESHOLD) {
            $this->sleepCounter++;
            if ($this->sleepCounter >= self::SLEEP_FRAMES) {
                $this->isSleeping = true;
                $this->velocity = Vec3::zero();
            }
        } else {
            $this->sleepCounter = 0;
            $this->isSleeping = false;
        }
    }
}
