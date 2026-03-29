<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Physics;

use PHPUnit\Framework\TestCase;
use PHPolygon\Math\Vec3;
use PHPolygon\Physics\SpatialHash3D;

class SpatialHash3DTest extends TestCase
{
    public function testEmptyHashReturnsNoPairs(): void
    {
        $hash = new SpatialHash3D(2.0);
        $this->assertEmpty($hash->queryPairs());
    }

    public function testSingleEntityReturnsNoPairs(): void
    {
        $hash = new SpatialHash3D(2.0);
        $hash->insert(1, new Vec3(0, 0, 0), new Vec3(1, 1, 1));
        $this->assertEmpty($hash->queryPairs());
    }

    public function testOverlappingEntitiesReturnPair(): void
    {
        $hash = new SpatialHash3D(2.0);
        $hash->insert(1, new Vec3(0, 0, 0), new Vec3(1, 1, 1));
        $hash->insert(2, new Vec3(0.5, 0, 0), new Vec3(1.5, 1, 1));
        $pairs = $hash->queryPairs();
        $this->assertCount(1, $pairs);
        $this->assertSame([1, 2], $pairs[0]);
    }

    public function testDistantEntitiesReturnNoPairs(): void
    {
        $hash = new SpatialHash3D(2.0);
        $hash->insert(1, new Vec3(0, 0, 0), new Vec3(1, 1, 1));
        $hash->insert(2, new Vec3(10, 10, 10), new Vec3(11, 11, 11));
        $this->assertEmpty($hash->queryPairs());
    }

    public function testNoDuplicatePairs(): void
    {
        $hash = new SpatialHash3D(1.0);
        // Two entities spanning multiple shared cells
        $hash->insert(1, new Vec3(0, 0, 0), new Vec3(2, 2, 2));
        $hash->insert(2, new Vec3(0.5, 0.5, 0.5), new Vec3(2.5, 2.5, 2.5));
        $pairs = $hash->queryPairs();
        // Should have exactly 1 unique pair despite sharing many cells
        $this->assertCount(1, $pairs);
    }

    public function testClearRemovesAll(): void
    {
        $hash = new SpatialHash3D(2.0);
        $hash->insert(1, new Vec3(0, 0, 0), new Vec3(1, 1, 1));
        $hash->insert(2, new Vec3(0, 0, 0), new Vec3(1, 1, 1));
        $hash->clear();
        $this->assertEmpty($hash->queryPairs());
    }

    public function testMultiplePairs(): void
    {
        $hash = new SpatialHash3D(2.0);
        $hash->insert(1, new Vec3(0, 0, 0), new Vec3(1, 1, 1));
        $hash->insert(2, new Vec3(0.5, 0, 0), new Vec3(1.5, 1, 1));
        $hash->insert(3, new Vec3(1, 0, 0), new Vec3(1.8, 1, 1));
        $pairs = $hash->queryPairs();
        // All three share the same cell → 3 pairs: (1,2), (1,3), (2,3)
        $this->assertCount(3, $pairs);
    }

    public function testPairOrderIsConsistent(): void
    {
        $hash = new SpatialHash3D(2.0);
        $hash->insert(5, new Vec3(0, 0, 0), new Vec3(1, 1, 1));
        $hash->insert(3, new Vec3(0, 0, 0), new Vec3(1, 1, 1));
        $pairs = $hash->queryPairs();
        // Smaller ID always first
        $this->assertSame(3, $pairs[0][0]);
        $this->assertSame(5, $pairs[0][1]);
    }
}
