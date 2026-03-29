<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Prefab\Roof;

use PHPUnit\Framework\TestCase;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\Transform3D;
use PHPolygon\Geometry\BoxMesh;
use PHPolygon\Geometry\CylinderMesh;
use PHPolygon\Geometry\MeshRegistry;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Prefab\Roof\FlatRoofBuilder;
use PHPolygon\Prefab\Roof\GableRoofBuilder;
use PHPolygon\Prefab\Roof\HipRoofBuilder;
use PHPolygon\Prefab\Roof\MansardRoofBuilder;
use PHPolygon\Prefab\Roof\RoofBuilder;
use PHPolygon\Prefab\Roof\RoofMaterials;
use PHPolygon\Prefab\Roof\RoofResult;
use PHPolygon\Prefab\Roof\RoofType;
use PHPolygon\Prefab\Roof\ShedRoofBuilder;
use PHPolygon\Prefab\Roof\ThatchedRoofBuilder;
use PHPolygon\Scene\SceneBuilder;

class RoofBuilderTest extends TestCase
{
    private SceneBuilder $scene;
    private RoofMaterials $materials;
    private Vec3 $basePos;
    private Quaternion $baseRot;

    protected function setUp(): void
    {
        if (!MeshRegistry::has('box')) {
            MeshRegistry::register('box', BoxMesh::generate(2.0, 2.0, 2.0));
        }
        if (!MeshRegistry::has('cylinder')) {
            MeshRegistry::register('cylinder', CylinderMesh::generate(1.0, 2.0, 16));
        }

        $this->scene = new SceneBuilder();
        $this->materials = new RoofMaterials(
            panel: 'mat_panel',
            panelBack: 'mat_panel_back',
            ridge: 'mat_ridge',
            rafter: 'mat_rafter',
            gable: 'mat_gable',
        );
        $this->basePos = new Vec3(0.0, 3.0, 0.0);
        $this->baseRot = Quaternion::identity();
    }

    // --- Factory Tests ---

    public function testFactoryReturnsCorrectTypes(): void
    {
        $this->assertInstanceOf(GableRoofBuilder::class, RoofBuilder::gable(3.5, 3.0, 1.2));
        $this->assertInstanceOf(HipRoofBuilder::class, RoofBuilder::hip(3.5, 3.0, 1.2));
        $this->assertInstanceOf(FlatRoofBuilder::class, RoofBuilder::flat(3.5, 3.0));
        $this->assertInstanceOf(ShedRoofBuilder::class, RoofBuilder::shed(3.5, 3.0, 1.2));
        $this->assertInstanceOf(ThatchedRoofBuilder::class, RoofBuilder::thatched(3.5, 3.0, 1.2));
        $this->assertInstanceOf(MansardRoofBuilder::class, RoofBuilder::mansard(3.5, 3.0, 1.2));
    }

    // --- RoofMaterials Tests ---

    public function testMaterialsFallbacks(): void
    {
        $m = new RoofMaterials(panel: 'tile');
        $this->assertSame('tile', $m->panel);
        $this->assertSame('tile', $m->panelBack);
        $this->assertSame('tile', $m->ridge);
        $this->assertSame('tile', $m->rafter);
        $this->assertSame('tile', $m->gable);
    }

    public function testMaterialsRafterFallsBackToRidge(): void
    {
        $m = new RoofMaterials(panel: 'tile', ridge: 'wood');
        $this->assertSame('wood', $m->ridge);
        $this->assertSame('wood', $m->rafter);
    }

    public function testMaterialsExplicitOverride(): void
    {
        $m = new RoofMaterials(
            panel: 'a', panelBack: 'b', ridge: 'c', rafter: 'd', gable: 'e',
        );
        $this->assertSame('a', $m->panel);
        $this->assertSame('b', $m->panelBack);
        $this->assertSame('c', $m->ridge);
        $this->assertSame('d', $m->rafter);
        $this->assertSame('e', $m->gable);
    }

    // --- RoofType Enum ---

    public function testRoofTypeEnum(): void
    {
        $this->assertSame('gable', RoofType::Gable->value);
        $this->assertSame('hip', RoofType::Hip->value);
        $this->assertSame('flat', RoofType::Flat->value);
        $this->assertSame('shed', RoofType::Shed->value);
        $this->assertSame('thatched', RoofType::Thatched->value);
        $this->assertSame('mansard', RoofType::Mansard->value);
    }

    // --- Gable Roof ---

    public function testGableRoofEntityCount(): void
    {
        $roof = RoofBuilder::gable(3.5, 3.0, 1.2, rafterCount: 4);
        $result = $roof->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        // 2 panels + 1 ridge + 8 rafters + 4 gable halves = 15
        $this->assertSame(15, $result->entityCount);
        $this->assertCount(15, $this->scene->getDeclarations());
    }

    public function testGableRoofResult(): void
    {
        $result = RoofBuilder::gable(3.5, 3.0, 1.2)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        $this->assertInstanceOf(RoofResult::class, $result);
        $this->assertEqualsWithDelta(4.2, $result->ridgeY, 0.01); // 3.0 + 1.2
        $this->assertEqualsWithDelta(3.0, $result->eaveY, 0.01);
    }

    public function testGableRoofWithPrefix(): void
    {
        RoofBuilder::gable(3.5, 3.0, 1.2, rafterCount: 0)
            ->withPrefix('Hut')
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        $names = array_map(fn($d) => $d->getName(), $this->scene->getDeclarations());
        foreach ($names as $name) {
            $this->assertStringStartsWith('Hut', $name);
        }
    }

    public function testGableRoofMaterialAssignment(): void
    {
        RoofBuilder::gable(3.5, 3.0, 1.2, rafterCount: 0)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        $decls = $this->scene->getDeclarations();
        $meshRenderers = [];
        foreach ($decls as $decl) {
            foreach ($decl->getComponents() as $comp) {
                if ($comp instanceof MeshRenderer) {
                    $meshRenderers[$decl->getName()] = $comp->materialId;
                }
            }
        }

        $this->assertSame('mat_panel', $meshRenderers['_RoofFront']);
        $this->assertSame('mat_panel_back', $meshRenderers['_RoofBack']);
        $this->assertSame('mat_ridge', $meshRenderers['_Ridge']);
        $this->assertSame('mat_gable', $meshRenderers['_GableLF']);
        $this->assertSame('mat_gable', $meshRenderers['_GableRF']);
    }

    public function testGableRoofNoRafters(): void
    {
        $result = RoofBuilder::gable(3.5, 3.0, 1.2, rafterCount: 0)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        // 2 panels + 1 ridge + 0 rafters + 4 gable halves = 7
        $this->assertSame(7, $result->entityCount);
    }

    // --- Thatched Roof ---

    public function testThatchedRoofAsymmetricSpans(): void
    {
        $roof = RoofBuilder::thatched(3.5, 3.0, 1.2, frontExtension: 1.5, rafterCount: 0);
        $result = $roof->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        // 2 panels + 1 ridge + 4 gable halves = 7
        $this->assertSame(7, $result->entityCount);
    }

    public function testThatchedRoofWithRafters(): void
    {
        $result = RoofBuilder::thatched(3.5, 3.0, 1.2, rafterCount: 4)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        // 2 panels + 1 ridge + 8 rafters + 4 gable halves = 15
        $this->assertSame(15, $result->entityCount);
    }

    // --- Hip Roof ---

    public function testHipRoofNoGables(): void
    {
        $result = RoofBuilder::hip(4.0, 3.0, 1.5, rafterCount: 0)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        $names = array_map(fn($d) => $d->getName(), $this->scene->getDeclarations());
        $gables = array_filter($names, fn($n) => str_contains($n, 'Gable'));
        $this->assertEmpty($gables, 'Hip roof should have no gable walls');

        // 4 panels + 1 ridge + 0 gables = 5
        $this->assertSame(5, $result->entityCount); // Hip has no gables
    }

    public function testHipRoofFourPanels(): void
    {
        RoofBuilder::hip(4.0, 3.0, 1.5, rafterCount: 0)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        $names = array_map(fn($d) => $d->getName(), $this->scene->getDeclarations());
        $roofPanels = array_filter($names, fn($n) => str_contains($n, 'Roof'));
        $this->assertCount(4, $roofPanels);
    }

    // --- Flat Roof ---

    public function testFlatRoofSingleEntity(): void
    {
        $result = RoofBuilder::flat(3.5, 3.0)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        $this->assertSame(1, $result->entityCount);
        $this->assertCount(1, $this->scene->getDeclarations());
    }

    // --- Shed Roof ---

    public function testShedRoofHasGables(): void
    {
        $result = RoofBuilder::shed(3.5, 3.0, 1.0, rafterCount: 0)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        $names = array_map(fn($d) => $d->getName(), $this->scene->getDeclarations());
        $gables = array_filter($names, fn($n) => str_contains($n, 'Gable'));
        $this->assertCount(4, $gables);

        // 1 panel + 4 gable halves = 5
        $this->assertSame(5, $result->entityCount);
    }

    // --- Mansard Roof ---

    public function testMansardRoofFourPanels(): void
    {
        $result = RoofBuilder::mansard(4.0, 3.5, 2.0, rafterCount: 0)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        $names = array_map(fn($d) => $d->getName(), $this->scene->getDeclarations());
        $roofPanels = array_filter($names, fn($n) => str_contains($n, 'Roof'));
        $this->assertCount(4, $roofPanels, 'Mansard should have 4 panels (2 lower + 2 upper)');
    }

    // --- Rotation Tests ---

    public function testRoofWithYawRotation(): void
    {
        $yawRot = Quaternion::fromEuler(0.0, 0.4, 0.0);
        $result = RoofBuilder::gable(3.5, 3.0, 1.2, rafterCount: 0)
            ->build($this->scene, $this->basePos, $yawRot, $this->materials);

        // Should still produce the same structure
        $this->assertSame(7, $result->entityCount);

        // Ridge Y should be the same regardless of yaw
        $this->assertEqualsWithDelta(4.2, $result->ridgeY, 0.01);
    }

    public function testRoofEntitiesHaveTransformAndMesh(): void
    {
        RoofBuilder::gable(3.5, 3.0, 1.2, rafterCount: 2)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        foreach ($this->scene->getDeclarations() as $decl) {
            $hasTransform = false;
            $hasMesh = false;
            foreach ($decl->getComponents() as $comp) {
                if ($comp instanceof Transform3D) $hasTransform = true;
                if ($comp instanceof MeshRenderer) $hasMesh = true;
            }
            $this->assertTrue($hasTransform, "Entity {$decl->getName()} missing Transform3D");
            $this->assertTrue($hasMesh, "Entity {$decl->getName()} missing MeshRenderer");
        }
    }

    // --- Edge Cases ---

    public function testZeroRafterCount(): void
    {
        $result = RoofBuilder::gable(3.5, 3.0, 1.2, rafterCount: 0)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        $names = array_map(fn($d) => $d->getName(), $this->scene->getDeclarations());
        $rafters = array_filter($names, fn($n) => str_contains($n, 'Rafter'));
        $this->assertEmpty($rafters);
    }

    public function testVerySmallRoof(): void
    {
        $result = RoofBuilder::gable(0.5, 0.5, 0.3, rafterCount: 1)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        $this->assertGreaterThan(0, $result->entityCount);
        $this->assertEqualsWithDelta(3.3, $result->ridgeY, 0.01);
    }

    public function testLargeRoof(): void
    {
        $result = RoofBuilder::gable(20.0, 15.0, 5.0, rafterCount: 10)
            ->build($this->scene, $this->basePos, $this->baseRot, $this->materials);

        // 2 panels + 1 ridge + 20 rafters + 4 gable halves = 27
        $this->assertSame(27, $result->entityCount);
        $this->assertEqualsWithDelta(8.0, $result->ridgeY, 0.01);
    }
}
