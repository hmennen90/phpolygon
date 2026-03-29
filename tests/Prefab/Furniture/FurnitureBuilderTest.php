<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Prefab\Furniture;

use PHPUnit\Framework\TestCase;
use PHPolygon\Component\BoxCollider3D;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\PointLight;
use PHPolygon\Component\Transform3D;
use PHPolygon\Geometry\BoxMesh;
use PHPolygon\Geometry\CylinderMesh;
use PHPolygon\Geometry\MeshRegistry;
use PHPolygon\Geometry\SphereMesh;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Prefab\Furniture\CrateBuilder;
use PHPolygon\Prefab\Furniture\FurnitureMaterials;
use PHPolygon\Prefab\Furniture\HammockBuilder;
use PHPolygon\Prefab\Furniture\LanternBuilder;
use PHPolygon\Prefab\Furniture\ShelfBuilder;
use PHPolygon\Prefab\Furniture\TableBuilder;
use PHPolygon\Prefab\Furniture\WindowBuilder;
use PHPolygon\Scene\SceneBuilder;

class FurnitureBuilderTest extends TestCase
{
    private SceneBuilder $scene;
    private FurnitureMaterials $mat;
    private Vec3 $pos;
    private Quaternion $rot;

    protected function setUp(): void
    {
        if (!MeshRegistry::has('box')) {
            MeshRegistry::register('box', BoxMesh::generate(2.0, 2.0, 2.0));
            MeshRegistry::register('cylinder', CylinderMesh::generate(1.0, 2.0, 16));
            MeshRegistry::register('sphere', SphereMesh::generate(1.0, 8, 12));
        }
        $this->scene = new SceneBuilder();
        $this->mat = new FurnitureMaterials(primary: 'wood', secondary: 'beam', fabric: 'cloth', metal: 'iron');
        $this->pos = new Vec3(0.0, 0.0, 0.0);
        $this->rot = Quaternion::identity();
    }

    // --- FurnitureMaterials ---

    public function testMaterialsFallback(): void
    {
        $m = new FurnitureMaterials(primary: 'oak');
        $this->assertSame('oak', $m->primary);
        $this->assertSame('oak', $m->secondary);
        $this->assertSame('oak', $m->fabric);
        $this->assertSame('oak', $m->metal);
    }

    // --- TableBuilder ---

    public function testRectangularTable(): void
    {
        $result = TableBuilder::rectangular(width: 1.0, depth: 0.7, height: 0.75)
            ->withPrefix('T')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $this->assertSame(5, $result->entityCount); // top + 4 legs
        $this->assertCount(5, $this->scene->getDeclarations());
        $this->assertContains('T_TableTop', $result->entityNames);
    }

    public function testRoundTable(): void
    {
        $result = TableBuilder::round(radius: 0.4, height: 0.75)
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $this->assertSame(2, $result->entityCount); // top + pedestal
    }

    public function testTableHasCollider(): void
    {
        TableBuilder::rectangular()->withPrefix('T')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $hasCollider = false;
        foreach ($this->scene->getDeclarations()[0]->getComponents() as $c) {
            if ($c instanceof BoxCollider3D) $hasCollider = true;
        }
        $this->assertTrue($hasCollider);
    }

    // --- HammockBuilder ---

    public function testHammock(): void
    {
        $result = HammockBuilder::standard(length: 1.6, postHeight: 1.2)
            ->withPrefix('H')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $this->assertSame(5, $result->entityCount); // 2 posts + body + 2 ropes
        $names = $result->entityNames;
        $this->assertContains('H_HammockPost_0', $names);
        $this->assertContains('H_HammockBody', $names);
        $this->assertContains('H_HammockRope_0', $names);
    }

    // --- LanternBuilder ---

    public function testHangingLantern(): void
    {
        $result = LanternBuilder::hanging(ropeLength: 0.5)
            ->withPrefix('L')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $this->assertSame(3, $result->entityCount); // rope + body + light
        $this->assertContains('L_LanternRope', $result->entityNames);
        $this->assertContains('L_Lantern', $result->entityNames);
        $this->assertContains('L_LanternLight', $result->entityNames);
    }

    public function testLanternHasPointLight(): void
    {
        LanternBuilder::hanging()->withPrefix('L')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $hasLight = false;
        foreach ($this->scene->getDeclarations() as $d) {
            foreach ($d->getComponents() as $c) {
                if ($c instanceof PointLight) $hasLight = true;
            }
        }
        $this->assertTrue($hasLight);
    }

    public function testStandingLantern(): void
    {
        $result = LanternBuilder::standing()
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $this->assertSame(2, $result->entityCount); // body + light (no rope)
    }

    // --- WindowBuilder ---

    public function testCrossWindow(): void
    {
        $result = WindowBuilder::cross(width: 0.7, height: 0.5)
            ->withPrefix('W')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $this->assertSame(2, $result->entityCount); // H bar + V bar
    }

    public function testWindowWithFrame(): void
    {
        $result = WindowBuilder::cross(width: 0.7, height: 0.5)
            ->withFrame()
            ->withPrefix('W')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $this->assertSame(6, $result->entityCount); // 2 bars + 4 frame parts
    }

    // --- ShelfBuilder ---

    public function testShelf(): void
    {
        $result = ShelfBuilder::standard(width: 0.8, height: 1.2, depth: 0.3, shelves: 3)
            ->withPrefix('S')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        // 2 side panels + 4 boards (bottom + 3 shelves)
        $this->assertSame(6, $result->entityCount);
    }

    public function testShelfHasSideColliders(): void
    {
        ShelfBuilder::standard(shelves: 2)->withPrefix('S')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $collidersFound = 0;
        foreach ($this->scene->getDeclarations() as $d) {
            if (str_contains($d->getName(), 'Side')) {
                foreach ($d->getComponents() as $c) {
                    if ($c instanceof BoxCollider3D) $collidersFound++;
                }
            }
        }
        $this->assertSame(2, $collidersFound);
    }

    // --- CrateBuilder ---

    public function testCrate(): void
    {
        $result = CrateBuilder::wooden(width: 0.4, height: 0.3, depth: 0.4)
            ->withPrefix('C')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $this->assertSame(1, $result->entityCount);
    }

    public function testCrateHasCollider(): void
    {
        CrateBuilder::wooden()->withPrefix('C')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $hasCollider = false;
        foreach ($this->scene->getDeclarations()[0]->getComponents() as $c) {
            if ($c instanceof BoxCollider3D) $hasCollider = true;
        }
        $this->assertTrue($hasCollider);
    }

    // --- All Builders Have Transform + Mesh ---

    public function testAllBuildersProduceValidEntities(): void
    {
        $builders = [
            ['table', fn() => TableBuilder::rectangular()->build(new SceneBuilder(), $this->pos, $this->rot, $this->mat)],
            ['hammock', fn() => HammockBuilder::standard()->build(new SceneBuilder(), $this->pos, $this->rot, $this->mat)],
            ['lantern', fn() => LanternBuilder::hanging()->build(new SceneBuilder(), $this->pos, $this->rot, $this->mat)],
            ['window', fn() => WindowBuilder::cross()->build(new SceneBuilder(), $this->pos, $this->rot, $this->mat)],
            ['shelf', fn() => ShelfBuilder::standard()->build(new SceneBuilder(), $this->pos, $this->rot, $this->mat)],
            ['crate', fn() => CrateBuilder::wooden()->build(new SceneBuilder(), $this->pos, $this->rot, $this->mat)],
        ];

        foreach ($builders as [$name, $fn]) {
            $result = $fn();
            $this->assertGreaterThan(0, $result->entityCount, "{$name} should create at least 1 entity");
        }
    }

    // --- Prefix ---

    public function testPrefixApplied(): void
    {
        CrateBuilder::wooden()->withPrefix('MyHut')
            ->build($this->scene, $this->pos, $this->rot, $this->mat);

        $this->assertStringStartsWith('MyHut', $this->scene->getDeclarations()[0]->getName());
    }
}
