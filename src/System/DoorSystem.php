<?php

declare(strict_types=1);

namespace PHPolygon\System;

use PHPolygon\Component\HingeJoint;
use PHPolygon\Component\LinearJoint;
use PHPolygon\Component\Transform3D;
use PHPolygon\ECS\AbstractSystem;
use PHPolygon\ECS\World;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;

/**
 * Updates all HingeJoint and LinearJoint entities.
 * Integrates velocities, applies damping, clamps limits, and updates transforms.
 *
 * Register BEFORE Physics3DSystem so colliders are up-to-date for collision.
 */
class DoorSystem extends AbstractSystem
{
    public function update(World $world, float $dt): void
    {
        $this->updateHingeJoints($world, $dt);
        $this->updateLinearJoints($world, $dt);
    }

    private function updateHingeJoints(World $world, float $dt): void
    {
        foreach ($world->query(HingeJoint::class, Transform3D::class) as $entity) {
            $hinge = $world->getComponent($entity->id, HingeJoint::class);
            $transform = $world->getComponent($entity->id, Transform3D::class);

            // Capture base transform on first frame (closed position + orientation)
            if ($hinge->basePosition === null) {
                $hinge->basePosition = clone $transform->position;
                $hinge->baseRotation = clone $transform->rotation;
            }

            // Damping
            $hinge->angularVelocity *= max(0.0, 1.0 - $hinge->damping * $dt);
            if (abs($hinge->angularVelocity) < 0.001) {
                $hinge->angularVelocity = 0.0;
            }

            // Integrate
            $hinge->angle += $hinge->angularVelocity * $dt;

            // Clamp
            if ($hinge->angle <= $hinge->minAngle) {
                $hinge->angle = $hinge->minAngle;
                $hinge->angularVelocity = 0.0;
            }
            if ($hinge->angle >= $hinge->maxAngle) {
                $hinge->angle = $hinge->maxAngle;
                $hinge->angularVelocity = 0.0;
            }

            $baseRot = $hinge->baseRotation ?? Quaternion::identity();

            // Hinge rotation in local space, then combined with base rotation
            $hingeRot = Quaternion::fromAxisAngle($hinge->axis, $hinge->angle);
            $combinedRot = $baseRot->multiply($hingeRot);

            // Anchor point in world space (base position + rotated anchor offset)
            $anchorLocal = $baseRot->rotateVec3($hinge->anchorOffset);
            $anchorWorld = $hinge->basePosition->add($anchorLocal);

            // Door center = anchor + combined rotation applied to negative anchor offset
            $negAnchor = new Vec3(-$hinge->anchorOffset->x, -$hinge->anchorOffset->y, -$hinge->anchorOffset->z);
            $rotatedOffset = $combinedRot->rotateVec3($negAnchor);

            $transform->position = $anchorWorld->add($rotatedOffset);
            $transform->rotation = $combinedRot;
            $transform->worldMatrix = $transform->getLocalMatrix();
        }
    }

    private function updateLinearJoints(World $world, float $dt): void
    {
        foreach ($world->query(LinearJoint::class, Transform3D::class) as $entity) {
            $joint = $world->getComponent($entity->id, LinearJoint::class);
            $transform = $world->getComponent($entity->id, Transform3D::class);

            if ($joint->basePosition === null) {
                $joint->basePosition = clone $transform->position;
            }

            // Damping
            $joint->velocity *= max(0.0, 1.0 - $joint->damping * $dt);
            if (abs($joint->velocity) < 0.001) {
                $joint->velocity = 0.0;
            }

            // Integrate
            $joint->position += $joint->velocity * $dt;

            // Clamp
            if ($joint->position <= $joint->minPosition) {
                $joint->position = $joint->minPosition;
                $joint->velocity = 0.0;
            }
            if ($joint->position >= $joint->maxPosition) {
                $joint->position = $joint->maxPosition;
                $joint->velocity = 0.0;
            }

            // Slide along axis
            $offset = $joint->slideAxis->mul($joint->position);
            $transform->position = $joint->basePosition->add($offset);
            $transform->worldMatrix = $transform->getLocalMatrix();
        }
    }
}
