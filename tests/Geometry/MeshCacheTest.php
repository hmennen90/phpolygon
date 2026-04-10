<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Geometry;

use PHPUnit\Framework\TestCase;
use PHPolygon\Geometry\BoxMesh;
use PHPolygon\Geometry\MeshCache;
use PHPolygon\Geometry\MeshData;
use PHPolygon\Geometry\MeshRegistry;

class MeshCacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/phpolygon_mesh_cache_test_' . uniqid();
        mkdir($this->cacheDir, 0755, true);
        MeshCache::configure($this->cacheDir);
        MeshRegistry::clear();
    }

    protected function tearDown(): void
    {
        MeshCache::clear();
        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }
        MeshCache::reset();
    }

    public function testResolveCreatesCacheFile(): void
    {
        $mesh = MeshCache::resolve('test_box', fn() => BoxMesh::generate(1.0, 1.0, 1.0));

        $this->assertInstanceOf(MeshData::class, $mesh);
        $this->assertFileExists($this->cacheDir . '/test_box.mesh');
    }

    public function testResolveRegistersInMeshRegistry(): void
    {
        MeshCache::resolve('test_box', fn() => BoxMesh::generate(1.0, 1.0, 1.0));

        $this->assertTrue(MeshRegistry::has('test_box'));
        $this->assertNotNull(MeshRegistry::get('test_box'));
    }

    public function testResolveReadsCacheOnSecondCall(): void
    {
        $callCount = 0;
        $generator = function () use (&$callCount) {
            $callCount++;
            return BoxMesh::generate(1.0, 1.0, 1.0);
        };

        MeshCache::resolve('test_box', $generator);
        $this->assertSame(1, $callCount);

        MeshRegistry::clear();
        MeshCache::resolve('test_box', $generator);
        $this->assertSame(1, $callCount);
    }

    public function testVersionChangeTriggersRegeneration(): void
    {
        $callCount = 0;
        $generator = function () use (&$callCount) {
            $callCount++;
            return BoxMesh::generate(1.0, 1.0, 1.0);
        };

        MeshCache::resolve('test_box', $generator, 'v1');
        $this->assertSame(1, $callCount);

        MeshRegistry::clear();
        MeshCache::resolve('test_box', $generator, 'v2');
        $this->assertSame(2, $callCount);
    }

    public function testClear(): void
    {
        MeshCache::resolve('box_a', fn() => BoxMesh::generate(1.0, 1.0, 1.0));
        MeshCache::resolve('box_b', fn() => BoxMesh::generate(2.0, 2.0, 2.0));

        $this->assertFileExists($this->cacheDir . '/box_a.mesh');
        $this->assertFileExists($this->cacheDir . '/box_b.mesh');

        MeshCache::clear();

        $this->assertFileDoesNotExist($this->cacheDir . '/box_a.mesh');
        $this->assertFileDoesNotExist($this->cacheDir . '/box_b.mesh');
    }

    public function testClearOne(): void
    {
        MeshCache::resolve('box_a', fn() => BoxMesh::generate(1.0, 1.0, 1.0));
        MeshCache::resolve('box_b', fn() => BoxMesh::generate(2.0, 2.0, 2.0));

        MeshCache::clearOne('box_a');

        $this->assertFileDoesNotExist($this->cacheDir . '/box_a.mesh');
        $this->assertFileExists($this->cacheDir . '/box_b.mesh');
    }

    public function testResolveWithoutConfigureSkipsCache(): void
    {
        MeshCache::reset();

        $mesh = MeshCache::resolve('test_box', fn() => BoxMesh::generate(1.0, 1.0, 1.0));

        $this->assertInstanceOf(MeshData::class, $mesh);
        $this->assertTrue(MeshRegistry::has('test_box'));
        $this->assertEmpty(glob($this->cacheDir . '/*.mesh'));
    }

    public function testSanitizesUnsafeIds(): void
    {
        MeshCache::resolve('path/to/mesh', fn() => BoxMesh::generate(1.0, 1.0, 1.0));

        $this->assertFileExists($this->cacheDir . '/path_to_mesh.mesh');
    }

    public function testCachedDataMatchesOriginal(): void
    {
        $original = BoxMesh::generate(3.0, 2.0, 1.0);
        MeshCache::resolve('precise_box', fn() => $original);

        MeshRegistry::clear();
        $cached = MeshCache::resolve('precise_box', fn() => throw new \RuntimeException('Should not be called'));

        $this->assertSame(count($original->vertices), count($cached->vertices));
        $this->assertSame(count($original->indices), count($cached->indices));
        $this->assertSame($original->indices, $cached->indices);

        foreach ($original->vertices as $i => $val) {
            $this->assertEqualsWithDelta($val, $cached->vertices[$i], 1e-6);
        }
    }

    public function testIsConfigured(): void
    {
        $this->assertTrue(MeshCache::isConfigured());

        MeshCache::reset();
        $this->assertFalse(MeshCache::isConfigured());
    }
}
