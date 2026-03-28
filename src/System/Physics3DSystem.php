<?php

declare(strict_types=1);

namespace PHPolygon\System;

use PHPolygon\Component\CharacterController3D;
use PHPolygon\Component\Transform3D;
use PHPolygon\ECS\AbstractSystem;
use PHPolygon\ECS\World;
use PHPolygon\Math\Vec3;

class Physics3DSystem extends AbstractSystem
{
    private Vec3 $gravity;

    public function __construct(?Vec3 $gravity = null)
    {
        $this->gravity = $gravity ?? new Vec3(0.0, -9.81, 0.0);
    }

    public function setGravity(Vec3 $gravity): void
    {
        $this->gravity = $gravity;
    }

    public function getGravity(): Vec3
    {
        return $this->gravity;
    }

    public function update(World $world, float $dt): void
    {
        foreach ($world->query(CharacterController3D::class, Transform3D::class) as $entity) {
            $controller = $entity->get(CharacterController3D::class);
            $transform  = $entity->get(Transform3D::class);

            // Apply gravity to vertical velocity
            if (!$controller->isGrounded) {
                $controller->velocity = $controller->velocity->add($this->gravity->mul($dt));
            }

            // Integrate velocity into position
            $newPos = $transform->position->add($controller->velocity->mul($dt));

            // Ground detection: AABB approximation — floor at y = 0
            $halfHeight = $controller->height / 2.0;
            if ($newPos->y - $halfHeight <= 0.0) {
                $newPos = new Vec3($newPos->x, $halfHeight, $newPos->z);
                $controller->velocity = new Vec3($controller->velocity->x, 0.0, $controller->velocity->z);
                $controller->isGrounded = true;
            } else {
                $controller->isGrounded = false;
            }

            $transform->position = $newPos;
            // Update world matrix to reflect new position
            $transform->worldMatrix = $transform->getLocalMatrix();
        }
    }
}
