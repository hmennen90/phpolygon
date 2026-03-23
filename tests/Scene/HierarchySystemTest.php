<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Scene;

use PHPUnit\Framework\TestCase;
use PHPolygon\Component\Transform2D;
use PHPolygon\ECS\World;
use PHPolygon\Math\Vec2;
use PHPolygon\Scene\HierarchySystem;

class HierarchySystemTest extends TestCase
{
    private World $world;
    private HierarchySystem $hierarchy;

    protected function setUp(): void
    {
        $this->world = new World();
        $this->hierarchy = new HierarchySystem();
        $this->world->addSystem($this->hierarchy);
    }

    public function testRootEntityWorldMatrixEqualsLocal(): void
    {
        $entity = $this->world->createEntity();
        $entity->attach(new Transform2D(position: new Vec2(10, 20)));

        $this->world->update(0.016);

        $transform = $entity->get(Transform2D::class);
        $worldPos = $transform->getWorldPosition();

        $this->assertEqualsWithDelta(10.0, $worldPos->x, 0.001);
        $this->assertEqualsWithDelta(20.0, $worldPos->y, 0.001);
    }

    public function testChildInheritsParentPosition(): void
    {
        $parent = $this->world->createEntity();
        $parentTransform = new Transform2D(position: new Vec2(100, 50));
        $parent->attach($parentTransform);

        $child = $this->world->createEntity();
        $childTransform = new Transform2D(
            position: new Vec2(20, 10),
            parentEntityId: $parent->id,
        );
        $child->attach($childTransform);

        // Register child in parent
        $parentTransform->childEntityIds[] = $child->id;

        $this->world->update(0.016);

        $worldPos = $childTransform->getWorldPosition();
        $this->assertEqualsWithDelta(120.0, $worldPos->x, 0.001);
        $this->assertEqualsWithDelta(60.0, $worldPos->y, 0.001);
    }

    public function testDeepHierarchy(): void
    {
        // Grandparent -> Parent -> Child
        $gp = $this->world->createEntity();
        $gpT = new Transform2D(position: new Vec2(100, 0));
        $gp->attach($gpT);

        $p = $this->world->createEntity();
        $pT = new Transform2D(position: new Vec2(50, 0), parentEntityId: $gp->id);
        $p->attach($pT);
        $gpT->childEntityIds[] = $p->id;

        $c = $this->world->createEntity();
        $cT = new Transform2D(position: new Vec2(25, 0), parentEntityId: $p->id);
        $c->attach($cT);
        $pT->childEntityIds[] = $c->id;

        $this->world->update(0.016);

        $worldPos = $cT->getWorldPosition();
        $this->assertEqualsWithDelta(175.0, $worldPos->x, 0.001);
        $this->assertEqualsWithDelta(0.0, $worldPos->y, 0.001);
    }

    public function testChildWithRotatedParent(): void
    {
        $parent = $this->world->createEntity();
        $parentTransform = new Transform2D(position: new Vec2(0, 0), rotation: 90.0);
        $parent->attach($parentTransform);

        $child = $this->world->createEntity();
        $childTransform = new Transform2D(
            position: new Vec2(10, 0),
            parentEntityId: $parent->id,
        );
        $child->attach($childTransform);
        $parentTransform->childEntityIds[] = $child->id;

        $this->world->update(0.016);

        $worldPos = $childTransform->getWorldPosition();
        // 90 degrees rotated: (10, 0) becomes (0, 10)
        $this->assertEqualsWithDelta(0.0, $worldPos->x, 0.01);
        $this->assertEqualsWithDelta(10.0, $worldPos->y, 0.01);
    }

    public function testDestroyParentCascadesToChildren(): void
    {
        $parent = $this->world->createEntity();
        $parentTransform = new Transform2D();
        $parent->attach($parentTransform);

        $child = $this->world->createEntity();
        $childTransform = new Transform2D(parentEntityId: $parent->id);
        $child->attach($childTransform);
        $parentTransform->childEntityIds[] = $child->id;

        $childId = $child->id;
        $parent->destroy();

        $this->assertFalse($this->world->isAlive($childId));
    }
}
