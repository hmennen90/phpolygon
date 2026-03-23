<?php

declare(strict_types=1);

namespace PHPolygon\Scene;

use PHPolygon\Component\NameTag;
use PHPolygon\Component\Transform2D;
use PHPolygon\ECS\ComponentInterface;
use PHPolygon\ECS\Entity;
use PHPolygon\ECS\World;

class SceneBuilder
{
    /** @var list<EntityDeclaration> */
    private array $declarations = [];

    public function entity(string $name): EntityDeclaration
    {
        $decl = new EntityDeclaration($name, $this);
        $this->declarations[] = $decl;
        return $decl;
    }

    /**
     * @param class-string<PrefabInterface> $prefabClass
     */
    public function prefab(string $prefabClass, string $name): EntityDeclaration
    {
        /** @var PrefabInterface $prefab */
        $prefab = new $prefabClass();
        $decl = $prefab->build($this);
        $decl->setPrefabSource($prefabClass);
        return $decl;
    }

    /** @return list<EntityDeclaration> */
    public function getDeclarations(): array
    {
        return $this->declarations;
    }

    /**
     * Materialize all declarations into a World, returning created entity IDs.
     *
     * @return array<string, int> Map of declaration name => entity ID
     */
    public function materialize(World $world): array
    {
        $map = [];
        foreach ($this->declarations as $decl) {
            $this->materializeDeclaration($decl, $world, null, $map);
        }
        return $map;
    }

    /**
     * @param array<string, int> $map
     */
    private function materializeDeclaration(
        EntityDeclaration $decl,
        World $world,
        ?int $parentId,
        array &$map,
    ): int {
        $entity = $world->createEntity();
        $id = $entity->id;
        $map[$decl->getName()] = $id;

        // Auto-attach NameTag
        $hasNameTag = false;
        foreach ($decl->getComponents() as $component) {
            if ($component instanceof NameTag) {
                $hasNameTag = true;
            }
            $entity->attach(clone $component);
        }
        if (!$hasNameTag) {
            $entity->attach(new NameTag($decl->getName()));
        }

        // Set parent on Transform2D if this is a child
        if ($parentId !== null) {
            $transform = $world->tryGetComponent($id, Transform2D::class);
            if ($transform instanceof Transform2D) {
                $transform->parentEntityId = $parentId;
            }

            // Add to parent's child list
            $parentTransform = $world->tryGetComponent($parentId, Transform2D::class);
            if ($parentTransform instanceof Transform2D) {
                $parentTransform->childEntityIds[] = $id;
            }
        }

        // Materialize children
        foreach ($decl->getChildren() as $childDecl) {
            $this->materializeDeclaration($childDecl, $world, $id, $map);
        }

        return $id;
    }
}
