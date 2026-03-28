<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Component;

use PHPUnit\Framework\TestCase;
use PHPolygon\Component\Camera3DComponent;
use PHPolygon\Component\CharacterController3D;
use PHPolygon\Component\DirectionalLight;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\ProjectionType;
use PHPolygon\Component\Transform3D;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;

class Component3DTest extends TestCase
{
    public function testTransform3DDefaultValues(): void
    {
        $t = new Transform3D();
        $this->assertEqualsWithDelta(0.0, $t->position->x, 1e-6);
        $this->assertEqualsWithDelta(0.0, $t->position->y, 1e-6);
        $this->assertEqualsWithDelta(0.0, $t->position->z, 1e-6);
        $this->assertEqualsWithDelta(1.0, $t->scale->x, 1e-6);
        $this->assertEqualsWithDelta(1.0, $t->scale->y, 1e-6);
        $this->assertEqualsWithDelta(1.0, $t->scale->z, 1e-6);
        $this->assertNull($t->parentEntityId);
        $this->assertEmpty($t->childEntityIds);
    }

    public function testTransform3DLocalMatrixTranslation(): void
    {
        $t = new Transform3D(new Vec3(5.0, 3.0, 2.0));
        $m = $t->getLocalMatrix();
        $translation = $m->getTranslation();
        $this->assertEqualsWithDelta(5.0, $translation->x, 1e-6);
        $this->assertEqualsWithDelta(3.0, $translation->y, 1e-6);
        $this->assertEqualsWithDelta(2.0, $translation->z, 1e-6);
    }

    public function testTransform3DLocalMatrixWithRotation(): void
    {
        $q = Quaternion::fromAxisAngle(new Vec3(0.0, 1.0, 0.0), M_PI / 2);
        $t = new Transform3D(Vec3::zero(), $q);
        $m = $t->getLocalMatrix();

        // X-axis (1,0,0) rotated 90° around Y becomes (0,0,-1)
        $result = $m->transformDirection(new Vec3(1.0, 0.0, 0.0));
        $this->assertEqualsWithDelta(0.0, $result->x, 1e-5);
        $this->assertEqualsWithDelta(0.0, $result->y, 1e-5);
        $this->assertEqualsWithDelta(-1.0, $result->z, 1e-5);
    }

    public function testTransform3DGetWorldPositionFromWorldMatrix(): void
    {
        $t = new Transform3D(new Vec3(1.0, 2.0, 3.0));
        // worldMatrix starts as identity; update it manually for the test
        $t->worldMatrix = $t->getLocalMatrix();
        $wp = $t->getWorldPosition();
        $this->assertEqualsWithDelta(1.0, $wp->x, 1e-6);
        $this->assertEqualsWithDelta(2.0, $wp->y, 1e-6);
        $this->assertEqualsWithDelta(3.0, $wp->z, 1e-6);
    }

    public function testCamera3DComponentDefaults(): void
    {
        $c = new Camera3DComponent();
        $this->assertEqualsWithDelta(60.0, $c->fov, 1e-6);
        $this->assertEqualsWithDelta(0.1, $c->near, 1e-6);
        $this->assertEqualsWithDelta(1000.0, $c->far, 1e-6);
        $this->assertEquals(ProjectionType::Perspective, $c->projectionType);
        $this->assertTrue($c->active);
    }

    public function testMeshRendererStoresIds(): void
    {
        $mr = new MeshRenderer('box_1x1x1', 'stone_wall', false);
        $this->assertEquals('box_1x1x1', $mr->meshId);
        $this->assertEquals('stone_wall', $mr->materialId);
        $this->assertFalse($mr->castShadows);
    }

    public function testMeshRendererDefaults(): void
    {
        $mr = new MeshRenderer();
        $this->assertEquals('', $mr->meshId);
        $this->assertEquals('', $mr->materialId);
        $this->assertTrue($mr->castShadows);
    }

    public function testDirectionalLightDefaultDirection(): void
    {
        $l = new DirectionalLight();
        $this->assertEqualsWithDelta(0.0, $l->direction->x, 1e-6);
        $this->assertEqualsWithDelta(-1.0, $l->direction->y, 1e-6);
        $this->assertEqualsWithDelta(0.0, $l->direction->z, 1e-6);
        $this->assertEqualsWithDelta(1.0, $l->intensity, 1e-6);
    }

    public function testCharacterController3DDefaults(): void
    {
        $cc = new CharacterController3D();
        $this->assertEqualsWithDelta(1.8, $cc->height, 1e-6);
        $this->assertEqualsWithDelta(0.4, $cc->radius, 1e-6);
        $this->assertFalse($cc->isGrounded);
        $this->assertEqualsWithDelta(0.0, $cc->velocity->x, 1e-6);
        $this->assertEqualsWithDelta(0.0, $cc->velocity->y, 1e-6);
        $this->assertEqualsWithDelta(0.0, $cc->velocity->z, 1e-6);
    }
}
