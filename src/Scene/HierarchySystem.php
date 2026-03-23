<?php

declare(strict_types=1);

namespace PHPolygon\Scene;

use PHPolygon\Component\Transform2D;
use PHPolygon\ECS\AbstractSystem;
use PHPolygon\ECS\World;

class HierarchySystem extends AbstractSystem
{
    public function update(World $world, float $dt): void
    {
        // Process all entities with Transform2D — roots first, then children
        foreach ($world->query(Transform2D::class) as $entity) {
            $transform = $world->getComponent($entity->id, Transform2D::class);

            // Only process root entities here; children are processed recursively
            if ($transform->parentEntityId === null) {
                $this->computeWorldMatrix($world, $entity->id, $transform);
            }
        }
    }

    private function computeWorldMatrix(World $world, int $entityId, Transform2D $transform): void
    {
        // Root: world matrix = local matrix
        if ($transform->parentEntityId === null) {
            $transform->worldMatrix = $transform->getLocalMatrix();
        } else {
            // Child: world matrix = parent world matrix * local matrix
            $parentTransform = $world->tryGetComponent($transform->parentEntityId, Transform2D::class);
            if ($parentTransform instanceof Transform2D) {
                $transform->worldMatrix = $parentTransform->worldMatrix->multiply($transform->getLocalMatrix());
            } else {
                $transform->worldMatrix = $transform->getLocalMatrix();
            }
        }

        // Recurse into children
        foreach ($transform->childEntityIds as $childId) {
            if (!$world->isAlive($childId)) {
                continue;
            }
            $childTransform = $world->tryGetComponent($childId, Transform2D::class);
            if ($childTransform instanceof Transform2D) {
                $this->computeWorldMatrix($world, $childId, $childTransform);
            }
        }
    }
}
