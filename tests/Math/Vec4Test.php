<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Math;

use PHPUnit\Framework\TestCase;
use PHPolygon\Math\Vec3;
use PHPolygon\Math\Vec4;

class Vec4Test extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $v = new Vec4();
        $this->assertEquals(0.0, $v->x);
        $this->assertEquals(0.0, $v->y);
        $this->assertEquals(0.0, $v->z);
        $this->assertEquals(1.0, $v->w);
    }

    public function testZero(): void
    {
        $v = Vec4::zero();
        $this->assertTrue($v->equals(new Vec4(0.0, 0.0, 0.0, 0.0)));
    }

    public function testOne(): void
    {
        $v = Vec4::one();
        $this->assertTrue($v->equals(new Vec4(1.0, 1.0, 1.0, 1.0)));
    }

    public function testAdd(): void
    {
        $a = new Vec4(1.0, 2.0, 3.0, 4.0);
        $b = new Vec4(5.0, 6.0, 7.0, 8.0);
        $this->assertTrue($a->add($b)->equals(new Vec4(6.0, 8.0, 10.0, 12.0)));
    }

    public function testSub(): void
    {
        $a = new Vec4(5.0, 6.0, 7.0, 8.0);
        $b = new Vec4(1.0, 2.0, 3.0, 4.0);
        $this->assertTrue($a->sub($b)->equals(new Vec4(4.0, 4.0, 4.0, 4.0)));
    }

    public function testMul(): void
    {
        $v = new Vec4(1.0, 2.0, 3.0, 4.0);
        $this->assertTrue($v->mul(2.0)->equals(new Vec4(2.0, 4.0, 6.0, 8.0)));
    }

    public function testDiv(): void
    {
        $v = new Vec4(2.0, 4.0, 6.0, 8.0);
        $this->assertTrue($v->div(2.0)->equals(new Vec4(1.0, 2.0, 3.0, 4.0)));
    }

    public function testLength(): void
    {
        $v = new Vec4(1.0, 0.0, 0.0, 0.0);
        $this->assertEqualsWithDelta(1.0, $v->length(), 1e-6);

        $v2 = new Vec4(1.0, 1.0, 1.0, 1.0);
        $this->assertEqualsWithDelta(2.0, $v2->length(), 1e-6);
    }

    public function testNormalize(): void
    {
        $v = new Vec4(1.0, 2.0, 3.0, 4.0);
        $n = $v->normalize();
        $this->assertEqualsWithDelta(1.0, $n->length(), 1e-6);
    }

    public function testNormalizeZeroReturnsZero(): void
    {
        $v = Vec4::zero();
        $n = $v->normalize();
        $this->assertTrue($n->equals(Vec4::zero()));
    }

    public function testDot(): void
    {
        $a = new Vec4(1.0, 0.0, 0.0, 0.0);
        $b = new Vec4(0.0, 1.0, 0.0, 0.0);
        $this->assertEqualsWithDelta(0.0, $a->dot($b), 1e-6);

        $c = new Vec4(1.0, 2.0, 3.0, 4.0);
        $d = new Vec4(1.0, 2.0, 3.0, 4.0);
        $this->assertEqualsWithDelta(30.0, $c->dot($d), 1e-6);
    }

    public function testToVec3DropsW(): void
    {
        $v = new Vec4(1.0, 2.0, 3.0, 99.0);
        $v3 = $v->toVec3();
        $this->assertEqualsWithDelta(1.0, $v3->x, 1e-6);
        $this->assertEqualsWithDelta(2.0, $v3->y, 1e-6);
        $this->assertEqualsWithDelta(3.0, $v3->z, 1e-6);
    }

    public function testFromVec3(): void
    {
        $v3 = new Vec3(1.0, 2.0, 3.0);
        $v4 = Vec4::fromVec3($v3, 0.0);
        $this->assertEqualsWithDelta(1.0, $v4->x, 1e-6);
        $this->assertEqualsWithDelta(2.0, $v4->y, 1e-6);
        $this->assertEqualsWithDelta(3.0, $v4->z, 1e-6);
        $this->assertEqualsWithDelta(0.0, $v4->w, 1e-6);
    }

    public function testFromVec3DefaultW(): void
    {
        $v3 = new Vec3(1.0, 2.0, 3.0);
        $v4 = Vec4::fromVec3($v3);
        $this->assertEqualsWithDelta(1.0, $v4->w, 1e-6);
    }

    public function testFromVec3RoundTrip(): void
    {
        $v3 = new Vec3(4.0, 5.0, 6.0);
        $v4 = Vec4::fromVec3($v3);
        $restored = $v4->toVec3();
        $this->assertTrue($v3->equals($restored));
    }

    public function testToArrayFromArray(): void
    {
        $v = new Vec4(1.5, 2.5, 3.5, 4.5);
        $arr = $v->toArray();
        $restored = Vec4::fromArray($arr);
        $this->assertTrue($v->equals($restored));
    }

    public function testEquals(): void
    {
        $a = new Vec4(1.0, 2.0, 3.0, 4.0);
        $b = new Vec4(1.0, 2.0, 3.0, 4.0);
        $c = new Vec4(1.0, 2.0, 3.0, 5.0);
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testEqualsWithEpsilon(): void
    {
        $a = new Vec4(1.0, 2.0, 3.0, 4.0);
        $b = new Vec4(1.0000005, 2.0000005, 3.0000005, 4.0000005);
        $this->assertTrue($a->equals($b, 1e-5));
        $this->assertFalse($a->equals($b, 1e-8));
    }
}
