<?php

declare(strict_types=1);

namespace PHPolygon\Tests\System;

use PHPUnit\Framework\TestCase;
use PHPolygon\Component\BodyType;
use PHPolygon\Component\BoxCollider3D;
use PHPolygon\Component\CharacterController3D;
use PHPolygon\Component\RigidBody3D;
use PHPolygon\Component\Transform3D;
use PHPolygon\ECS\World;
use PHPolygon\Geometry\BoxMesh;
use PHPolygon\Geometry\MeshRegistry;
use PHPolygon\Math\Vec3;
use PHPolygon\System\RigidBody3DSystem;

class RigidBody3DSystemTest extends TestCase
{
    private World $world;
    private RigidBody3DSystem $system;

    protected function setUp(): void
    {
        $this->world = new World();
        $this->system = new RigidBody3DSystem();
    }

    private function createDynamicBox(Vec3 $position, float $mass = 1.0): int
    {
        $entity = $this->world->createEntity();
        $entity->attach(new Transform3D(position: $position, scale: new Vec3(0.5, 0.5, 0.5)));
        $entity->attach(new BoxCollider3D(size: new Vec3(2.0, 2.0, 2.0), isStatic: false));
        $entity->attach(new RigidBody3D(bodyType: BodyType::Dynamic, mass: $mass));
        return $entity->id;
    }

    private function createStaticFloor(float $y = 0.0): int
    {
        $entity = $this->world->createEntity();
        $entity->attach(new Transform3D(
            position: new Vec3(0, $y, 0),
            scale: new Vec3(50, 0.5, 50),
        ));
        $entity->attach(new BoxCollider3D(size: new Vec3(2.0, 2.0, 2.0), isStatic: true));
        return $entity->id;
    }

    // --- Gravity ---

    public function testDynamicBodyFallsWithGravity(): void
    {
        $id = $this->createDynamicBox(new Vec3(0, 5, 0));

        $this->system->update($this->world, 1.0 / 60.0);

        $transform = $this->world->getComponent($id, Transform3D::class);
        $this->assertLessThan(5.0, $transform->position->y, 'Body should fall');
    }

    public function testStaticBodyDoesNotFall(): void
    {
        $floorId = $this->createStaticFloor(3.0);

        $this->system->update($this->world, 1.0 / 60.0);

        $transform = $this->world->getComponent($floorId, Transform3D::class);
        $this->assertEqualsWithDelta(3.0, $transform->position->y, 0.001);
    }

    // --- Static Collision ---

    public function testDynamicBodyLandsOnFloor(): void
    {
        $this->createStaticFloor(0.0);
        $id = $this->createDynamicBox(new Vec3(0, 2, 0));

        // Simulate 120 frames (~2 seconds)
        for ($i = 0; $i < 120; $i++) {
            $this->system->update($this->world, 1.0 / 60.0);
        }

        $transform = $this->world->getComponent($id, Transform3D::class);
        // Should have landed near floor top (y ≈ 0.5 + box half height 0.5 = ~1.0)
        $this->assertGreaterThan(0.0, $transform->position->y, 'Should not fall through floor');
        $this->assertLessThan(2.0, $transform->position->y, 'Should have fallen from start');
    }

    // --- Impulse ---

    public function testAddImpulseMovesBody(): void
    {
        $id = $this->createDynamicBox(new Vec3(0, 0, 0));
        $rigid = $this->world->getComponent($id, RigidBody3D::class);

        $rigid->addImpulse(new Vec3(10, 0, 0));
        $this->system->update($this->world, 1.0 / 60.0);

        $transform = $this->world->getComponent($id, Transform3D::class);
        $this->assertGreaterThan(0.0, $transform->position->x);
    }

    public function testKinematicBodyIgnoresImpulse(): void
    {
        $entity = $this->world->createEntity();
        $entity->attach(new Transform3D(position: new Vec3(5, 0, 0)));
        $entity->attach(new BoxCollider3D(size: new Vec3(2, 2, 2)));
        $entity->attach(new RigidBody3D(bodyType: BodyType::Kinematic));
        $id = $entity->id;

        $rigid = $this->world->getComponent($id, RigidBody3D::class);
        $rigid->addImpulse(new Vec3(100, 0, 0));

        $this->system->update($this->world, 1.0 / 60.0);

        $transform = $this->world->getComponent($id, Transform3D::class);
        $this->assertEqualsWithDelta(5.0, $transform->position->x, 0.001, 'Kinematic body should not move from impulse');
    }

    // --- Sleep ---

    public function testBodyGoesToSleep(): void
    {
        $this->createStaticFloor(0.0);
        // Use zero restitution so the crate doesn't bounce
        $entity = $this->world->createEntity();
        $entity->attach(new Transform3D(position: new Vec3(0, 2, 0), scale: new Vec3(0.5, 0.5, 0.5)));
        $entity->attach(new BoxCollider3D(size: new Vec3(2.0, 2.0, 2.0), isStatic: false));
        $entity->attach(new RigidBody3D(bodyType: BodyType::Dynamic, mass: 1.0, restitution: 0.0));
        $id = $entity->id;

        // Simulate until body settles (fall + land + 60 sleep frames)
        for ($i = 0; $i < 300; $i++) {
            $this->system->update($this->world, 1.0 / 60.0);
        }

        $rigid = $this->world->getComponent($id, RigidBody3D::class);
        $this->assertTrue($rigid->isSleeping, 'Body should be sleeping after settling');
    }

    public function testSleepingBodyWakesOnImpulse(): void
    {
        $id = $this->createDynamicBox(new Vec3(0, 0, 0));
        $rigid = $this->world->getComponent($id, RigidBody3D::class);
        $rigid->isSleeping = true;
        $rigid->sleepCounter = 100;

        $rigid->addImpulse(new Vec3(5, 0, 0));
        $this->assertFalse($rigid->isSleeping);
        $this->assertSame(0, $rigid->sleepCounter);
    }

    // --- Inverse Mass ---

    public function testDynamicInverseMass(): void
    {
        $rigid = new RigidBody3D(mass: 4.0);
        $this->assertEqualsWithDelta(0.25, $rigid->getInverseMass(), 0.001);
    }

    public function testStaticInverseMassIsZero(): void
    {
        $rigid = new RigidBody3D(bodyType: BodyType::Static);
        $this->assertEqualsWithDelta(0.0, $rigid->getInverseMass(), 0.001);
    }

    public function testKinematicInverseMassIsZero(): void
    {
        $rigid = new RigidBody3D(bodyType: BodyType::Kinematic);
        $this->assertEqualsWithDelta(0.0, $rigid->getInverseMass(), 0.001);
    }

    // --- Body Type ---

    public function testBodyTypeEnum(): void
    {
        $this->assertSame('static', BodyType::Static->value);
        $this->assertSame('kinematic', BodyType::Kinematic->value);
        $this->assertSame('dynamic', BodyType::Dynamic->value);
    }

    // --- Character Push ---

    public function testCharacterPushesDynamicBody(): void
    {
        $crateId = $this->createDynamicBox(new Vec3(1, 0, 0), mass: 2.0);

        // Create character overlapping the crate
        $charEntity = $this->world->createEntity();
        $charEntity->attach(new Transform3D(position: new Vec3(0.5, 0, 0)));
        $controller = new CharacterController3D(height: 1.8, radius: 0.3);
        $controller->velocity = new Vec3(2, 0, 0); // Moving toward crate
        $charEntity->attach($controller);

        $this->system->update($this->world, 1.0 / 60.0);

        $crateTransform = $this->world->getComponent($crateId, Transform3D::class);
        $crateRigid = $this->world->getComponent($crateId, RigidBody3D::class);
        // Crate should have positive X velocity from being pushed
        $this->assertGreaterThan(0.0, $crateRigid->velocity->x, 'Crate should be pushed by character');
    }
}
