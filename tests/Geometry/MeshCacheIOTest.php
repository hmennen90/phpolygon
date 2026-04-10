<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Geometry;

use PHPUnit\Framework\TestCase;
use PHPolygon\Geometry\BoxMesh;
use PHPolygon\Geometry\CylinderMesh;
use PHPolygon\Geometry\MeshCacheIO;
use PHPolygon\Geometry\MeshData;
use PHPolygon\Geometry\PlaneMesh;
use PHPolygon\Geometry\SphereMesh;
use PHPolygon\Geometry\WedgeMesh;

class MeshCacheIOTest extends TestCase
{
    public function testRoundTripBox(): void
    {
        $original = BoxMesh::generate(2.0, 3.0, 1.5);
        $encoded = MeshCacheIO::encode($original, 'v1');
        $decoded = MeshCacheIO::decode($encoded);

        $this->assertNotNull($decoded);
        $this->assertSame(count($original->vertices), count($decoded->vertices));
        $this->assertSame(count($original->normals), count($decoded->normals));
        $this->assertSame(count($original->uvs), count($decoded->uvs));
        $this->assertSame($original->indices, $decoded->indices);
        $this->assertFloatsEqual($original->vertices, $decoded->vertices);
        $this->assertFloatsEqual($original->normals, $decoded->normals);
        $this->assertFloatsEqual($original->uvs, $decoded->uvs);
    }

    public function testRoundTripSphere(): void
    {
        $original = SphereMesh::generate(1.0, 12, 16);
        $decoded = MeshCacheIO::decode(MeshCacheIO::encode($original, '1'));

        $this->assertNotNull($decoded);
        $this->assertSame($original->vertexCount(), $decoded->vertexCount());
        $this->assertSame($original->triangleCount(), $decoded->triangleCount());
        $this->assertFloatsEqual($original->vertices, $decoded->vertices);
    }

    public function testRoundTripCylinder(): void
    {
        $original = CylinderMesh::generate(1.0, 2.0, 16);
        $decoded = MeshCacheIO::decode(MeshCacheIO::encode($original, '1'));

        $this->assertNotNull($decoded);
        $this->assertSame($original->vertexCount(), $decoded->vertexCount());
        $this->assertFloatsEqual($original->vertices, $decoded->vertices);
    }

    public function testRoundTripPlane(): void
    {
        $original = PlaneMesh::generate(10.0, 10.0);
        $decoded = MeshCacheIO::decode(MeshCacheIO::encode($original, '1'));

        $this->assertNotNull($decoded);
        $this->assertSame($original->vertexCount(), $decoded->vertexCount());
        $this->assertFloatsEqual($original->vertices, $decoded->vertices);
    }

    public function testRoundTripWedge(): void
    {
        $original = WedgeMesh::generate(0.5);
        $decoded = MeshCacheIO::decode(MeshCacheIO::encode($original, '1'));

        $this->assertNotNull($decoded);
        $this->assertSame($original->vertexCount(), $decoded->vertexCount());
        $this->assertFloatsEqual($original->vertices, $decoded->vertices);
    }

    public function testEmptyMesh(): void
    {
        $original = new MeshData([], [], [], []);
        $encoded = MeshCacheIO::encode($original, 'v1');
        $decoded = MeshCacheIO::decode($encoded);

        $this->assertNotNull($decoded);
        $this->assertSame([], $decoded->vertices);
        $this->assertSame([], $decoded->normals);
        $this->assertSame([], $decoded->uvs);
        $this->assertSame([], $decoded->indices);
    }

    public function testDecodeCorruptMagic(): void
    {
        $encoded = MeshCacheIO::encode(BoxMesh::generate(1.0, 1.0, 1.0), 'v1');
        $corrupted = 'XXXX' . substr($encoded, 4);

        $this->assertNull(MeshCacheIO::decode($corrupted));
    }

    public function testDecodeTruncatedHeader(): void
    {
        $this->assertNull(MeshCacheIO::decode('PHMC'));
        $this->assertNull(MeshCacheIO::decode(''));
    }

    public function testDecodeTruncatedData(): void
    {
        $encoded = MeshCacheIO::encode(BoxMesh::generate(1.0, 1.0, 1.0), 'v1');
        $truncated = substr($encoded, 0, 40);

        $this->assertNull(MeshCacheIO::decode($truncated));
    }

    public function testVersionHash(): void
    {
        $hash1 = MeshCacheIO::versionHash('v1');
        $hash2 = MeshCacheIO::versionHash('v2');
        $hash1Again = MeshCacheIO::versionHash('v1');

        $this->assertSame($hash1, $hash1Again);
        $this->assertNotSame($hash1, $hash2);
    }

    public function testReadVersionHash(): void
    {
        $encoded = MeshCacheIO::encode(BoxMesh::generate(1.0, 1.0, 1.0), 'myversion');
        $expected = MeshCacheIO::versionHash('myversion');

        $this->assertSame($expected, MeshCacheIO::readVersionHash($encoded));
    }

    public function testReadVersionHashFromCorruptData(): void
    {
        $this->assertNull(MeshCacheIO::readVersionHash('short'));
        $this->assertNull(MeshCacheIO::readVersionHash('XXXXxxxxxxxx'));
    }

    /**
     * @param float[] $expected
     * @param float[] $actual
     */
    private function assertFloatsEqual(array $expected, array $actual, float $delta = 1e-6): void
    {
        $this->assertSame(count($expected), count($actual));
        foreach ($expected as $i => $val) {
            $this->assertEqualsWithDelta($val, $actual[$i], $delta, "Float mismatch at index {$i}");
        }
    }
}
