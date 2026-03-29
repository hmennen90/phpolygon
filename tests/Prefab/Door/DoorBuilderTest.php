<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Prefab\Door;

use PHPUnit\Framework\TestCase;
use PHPolygon\Component\BoxCollider3D;
use PHPolygon\Component\HingeJoint;
use PHPolygon\Component\LinearJoint;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\Transform3D;
use PHPolygon\Geometry\BoxMesh;
use PHPolygon\Geometry\CylinderMesh;
use PHPolygon\Geometry\MeshRegistry;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Prefab\Door\DoorBuilder;
use PHPolygon\Prefab\Door\DoorMaterials;
use PHPolygon\Prefab\Door\DoorResult;
use PHPolygon\Prefab\Door\DoorType;
use PHPolygon\Prefab\Door\DoubleDoorBuilder;
use PHPolygon\Prefab\Door\RevolvingDoorBuilder;
use PHPolygon\Prefab\Door\SingleDoorBuilder;
use PHPolygon\Prefab\Door\SlidingDoorBuilder;
use PHPolygon\Prefab\Door\TrapdoorBuilder;
use PHPolygon\Scene\SceneBuilder;

class DoorBuilderTest extends TestCase
{
    private SceneBuilder $scene;
    private DoorMaterials $materials;
    private Vec3 $pos;
    private Quaternion $rot;

    protected function setUp(): void
    {
        if (!MeshRegistry::has('box')) {
            MeshRegistry::register('box', BoxMesh::generate(2.0, 2.0, 2.0));
        }
        $this->scene = new SceneBuilder();
        $this->materials = new DoorMaterials(panel: 'mat_door', frame: 'mat_frame', handle: 'mat_handle');
        $this->pos = new Vec3(5.0, 1.0, 3.0);
        $this->rot = Quaternion::identity();
    }

    // --- Factory ---

    public function testFactoryReturnsCorrectTypes(): void
    {
        $this->assertInstanceOf(SingleDoorBuilder::class, DoorBuilder::single());
        $this->assertInstanceOf(DoubleDoorBuilder::class, DoorBuilder::double());
        $this->assertInstanceOf(SlidingDoorBuilder::class, DoorBuilder::sliding());
        $this->assertInstanceOf(TrapdoorBuilder::class, DoorBuilder::trapdoor());
        $this->assertInstanceOf(RevolvingDoorBuilder::class, DoorBuilder::revolving());
    }

    // --- DoorType Enum ---

    public function testDoorTypeEnum(): void
    {
        $this->assertSame('single', DoorType::Single->value);
        $this->assertSame('double', DoorType::Double->value);
        $this->assertSame('sliding', DoorType::Sliding->value);
        $this->assertSame('trapdoor', DoorType::Trapdoor->value);
        $this->assertSame('revolving', DoorType::Revolving->value);
    }

    // --- DoorMaterials ---

    public function testMaterialsFallbacks(): void
    {
        $m = new DoorMaterials(panel: 'wood');
        $this->assertSame('wood', $m->panel);
        $this->assertSame('wood', $m->frame);
        $this->assertSame('wood', $m->handle);
    }

    // --- Single Door ---

    public function testSingleDoorCreatesOneEntity(): void
    {
        $result = DoorBuilder::single()->build($this->scene, $this->pos, $this->rot, $this->materials);
        $this->assertSame(1, $result->entityCount);
        $this->assertCount(1, $this->scene->getDeclarations());
    }

    public function testSingleDoorHasHingeJoint(): void
    {
        DoorBuilder::single()->build($this->scene, $this->pos, $this->rot, $this->materials);
        $comps = $this->scene->getDeclarations()[0]->getComponents();
        $hasHinge = false;
        foreach ($comps as $c) {
            if ($c instanceof HingeJoint) $hasHinge = true;
        }
        $this->assertTrue($hasHinge, 'Single door must have HingeJoint');
    }

    public function testSingleDoorLeftHinge(): void
    {
        DoorBuilder::single(hingeSide: 'left')->build($this->scene, $this->pos, $this->rot, $this->materials);
        $hinge = $this->findComponent(HingeJoint::class, 0);
        $this->assertLessThan(0, $hinge->anchorOffset->x, 'Left hinge should have negative X offset');
    }

    public function testSingleDoorRightHinge(): void
    {
        DoorBuilder::single(hingeSide: 'right')->build($this->scene, $this->pos, $this->rot, $this->materials);
        $hinge = $this->findComponent(HingeJoint::class, 0);
        $this->assertGreaterThan(0, $hinge->anchorOffset->x, 'Right hinge should have positive X offset');
    }

    public function testSingleDoorWithPrefix(): void
    {
        DoorBuilder::single()->withPrefix('Hut')->build($this->scene, $this->pos, $this->rot, $this->materials);
        $this->assertStringStartsWith('Hut', $this->scene->getDeclarations()[0]->getName());
    }

    public function testSingleDoorStaticCollider(): void
    {
        DoorBuilder::single()->build($this->scene, $this->pos, $this->rot, $this->materials);
        $collider = $this->findComponent(BoxCollider3D::class, 0);
        $this->assertTrue($collider->isStatic, 'Door collider is static (movement managed by DoorSystem)');
    }

    // --- Double Door ---

    public function testDoubleDoorCreatesTwoEntities(): void
    {
        $result = DoorBuilder::double()->build($this->scene, $this->pos, $this->rot, $this->materials);
        $this->assertSame(2, $result->entityCount);
        $this->assertCount(2, $this->scene->getDeclarations());
    }

    public function testDoubleDoorOpposingHinges(): void
    {
        DoorBuilder::double()->build($this->scene, $this->pos, $this->rot, $this->materials);
        $hingeL = $this->findComponent(HingeJoint::class, 0);
        $hingeR = $this->findComponent(HingeJoint::class, 1);
        // Left hinge on left edge (negative X), right hinge on right edge (positive X)
        $this->assertLessThan(0, $hingeL->anchorOffset->x);
        $this->assertGreaterThan(0, $hingeR->anchorOffset->x);
    }

    // --- Sliding Door ---

    public function testSlidingDoorHasLinearJoint(): void
    {
        DoorBuilder::sliding()->build($this->scene, $this->pos, $this->rot, $this->materials);
        $joint = $this->findComponent(LinearJoint::class, 0);
        $this->assertNotNull($joint);
        $this->assertGreaterThan(0, $joint->maxPosition);
    }

    public function testSlidingDoorNoHingeJoint(): void
    {
        DoorBuilder::sliding()->build($this->scene, $this->pos, $this->rot, $this->materials);
        $hasHinge = false;
        foreach ($this->scene->getDeclarations()[0]->getComponents() as $c) {
            if ($c instanceof HingeJoint) $hasHinge = true;
        }
        $this->assertFalse($hasHinge, 'Sliding door should not have HingeJoint');
    }

    // --- Trapdoor ---

    public function testTrapdoorHorizontalHinge(): void
    {
        DoorBuilder::trapdoor(hingeSide: 'back')->build($this->scene, $this->pos, $this->rot, $this->materials);
        $hinge = $this->findComponent(HingeJoint::class, 0);
        // Back hinge: axis should be X (horizontal)
        $this->assertEqualsWithDelta(1.0, $hinge->axis->x, 0.01);
        $this->assertEqualsWithDelta(0.0, $hinge->axis->y, 0.01);
    }

    public function testTrapdoorSideHinge(): void
    {
        DoorBuilder::trapdoor(hingeSide: 'left')->build($this->scene, $this->pos, $this->rot, $this->materials);
        $hinge = $this->findComponent(HingeJoint::class, 0);
        // Left hinge: axis should be Z (horizontal)
        $this->assertEqualsWithDelta(0.0, $hinge->axis->x, 0.01);
        $this->assertEqualsWithDelta(1.0, $hinge->axis->z, 0.01);
    }

    // --- Revolving Door ---

    public function testRevolvingDoorSegments(): void
    {
        $result = DoorBuilder::revolving(segments: 4)
            ->build($this->scene, $this->pos, $this->rot, $this->materials);
        $this->assertSame(4, $result->entityCount);
        $this->assertCount(4, $this->scene->getDeclarations());
    }

    public function testRevolvingDoorNoAngleLimits(): void
    {
        DoorBuilder::revolving(segments: 2)->build($this->scene, $this->pos, $this->rot, $this->materials);
        $hinge = $this->findComponent(HingeJoint::class, 0);
        $this->assertGreaterThan(100, $hinge->maxAngle, 'Revolving door should have very high max angle');
        $this->assertLessThan(-100, $hinge->minAngle, 'Revolving door should have very low min angle');
    }

    public function testRevolvingDoorCenteredHinge(): void
    {
        DoorBuilder::revolving(segments: 1)->build($this->scene, $this->pos, $this->rot, $this->materials);
        $hinge = $this->findComponent(HingeJoint::class, 0);
        $this->assertEqualsWithDelta(0.0, $hinge->anchorOffset->x, 0.01);
        $this->assertEqualsWithDelta(0.0, $hinge->anchorOffset->z, 0.01);
    }

    // --- Frame ---

    public function testSingleDoorWithFrame(): void
    {
        $result = DoorBuilder::single()->withFrame()->withPrefix('T')
            ->build($this->scene, $this->pos, $this->rot, $this->materials);
        // 1 door + 3 frame = 4
        $this->assertSame(4, $result->entityCount);
        $names = array_map(fn($d) => $d->getName(), $this->scene->getDeclarations());
        $this->assertContains('T_FrameL', $names);
        $this->assertContains('T_FrameR', $names);
        $this->assertContains('T_FrameT', $names);
        $this->assertContains('T_Door', $names);
    }

    public function testSingleDoorWithoutFrame(): void
    {
        $result = DoorBuilder::single()->build($this->scene, $this->pos, $this->rot, $this->materials);
        $this->assertSame(1, $result->entityCount);
        $names = array_map(fn($d) => $d->getName(), $this->scene->getDeclarations());
        $frames = array_filter($names, fn($n) => str_contains($n, 'Frame'));
        $this->assertEmpty($frames, 'No frame entities without withFrame()');
    }

    public function testDoubleDoorWithFrame(): void
    {
        $result = DoorBuilder::double()->withFrame()
            ->build($this->scene, $this->pos, $this->rot, $this->materials);
        // 2 doors + 3 frame = 5
        $this->assertSame(5, $result->entityCount);
    }

    public function testFrameMaterialFallback(): void
    {
        $m = new DoorMaterials(panel: 'wood_panel');
        DoorBuilder::single()->withFrame()->build($this->scene, $this->pos, $this->rot, $m);
        $decls = $this->scene->getDeclarations();
        foreach ($decls as $d) {
            if (str_contains($d->getName(), 'Frame')) {
                foreach ($d->getComponents() as $c) {
                    if ($c instanceof MeshRenderer) {
                        // frame falls back to panel
                        $this->assertSame('wood_panel', $c->materialId);
                    }
                }
            }
        }
    }

    public function testFrameEntitiesAreStatic(): void
    {
        DoorBuilder::single()->withFrame()->build($this->scene, $this->pos, $this->rot, $this->materials);
        foreach ($this->scene->getDeclarations() as $d) {
            if (str_contains($d->getName(), 'Frame')) {
                foreach ($d->getComponents() as $c) {
                    if ($c instanceof BoxCollider3D) {
                        $this->assertTrue($c->isStatic, "Frame {$d->getName()} should be static");
                    }
                }
            }
        }
    }

    // --- All Types Have Required Components ---

    public function testAllTypesHaveTransformMeshCollider(): void
    {
        $builders = [
            DoorBuilder::single(),
            DoorBuilder::double(),
            DoorBuilder::sliding(),
            DoorBuilder::trapdoor(),
            DoorBuilder::revolving(segments: 1),
        ];

        foreach ($builders as $builder) {
            $scene = new SceneBuilder();
            $builder->build($scene, $this->pos, $this->rot, $this->materials);

            foreach ($scene->getDeclarations() as $decl) {
                $has = ['Transform3D' => false, 'MeshRenderer' => false, 'BoxCollider3D' => false];
                foreach ($decl->getComponents() as $c) {
                    if ($c instanceof Transform3D) $has['Transform3D'] = true;
                    if ($c instanceof MeshRenderer) $has['MeshRenderer'] = true;
                    if ($c instanceof BoxCollider3D) $has['BoxCollider3D'] = true;
                }
                foreach ($has as $comp => $found) {
                    $this->assertTrue($found, get_class($builder) . " entity {$decl->getName()} missing {$comp}");
                }
            }
        }
    }

    private function findComponent(string $class, int $entityIndex): mixed
    {
        $decl = $this->scene->getDeclarations()[$entityIndex];
        foreach ($decl->getComponents() as $c) {
            if ($c instanceof $class) return $c;
        }
        return null;
    }
}
