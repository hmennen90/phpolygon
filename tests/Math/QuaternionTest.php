<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Math;

use PHPUnit\Framework\TestCase;
use PHPolygon\Math\Mat4;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;

class QuaternionTest extends TestCase
{
    public function testIdentityLeavesVec3Unchanged(): void
    {
        $q = Quaternion::identity();
        $v = new Vec3(1.0, 2.0, 3.0);
        $result = $q->rotateVec3($v);
        $this->assertEqualsWithDelta($v->x, $result->x, 1e-6);
        $this->assertEqualsWithDelta($v->y, $result->y, 1e-6);
        $this->assertEqualsWithDelta($v->z, $result->z, 1e-6);
    }

    public function testFromAxisAngleY90RotatesX(): void
    {
        $q = Quaternion::fromAxisAngle(new Vec3(0.0, 1.0, 0.0), M_PI / 2);
        $v = new Vec3(1.0, 0.0, 0.0);
        $result = $q->rotateVec3($v);
        $this->assertEqualsWithDelta(0.0, $result->x, 1e-6);
        $this->assertEqualsWithDelta(0.0, $result->y, 1e-6);
        $this->assertEqualsWithDelta(-1.0, $result->z, 1e-6);
    }

    public function testFromAxisAngleX90RotatesY(): void
    {
        $q = Quaternion::fromAxisAngle(new Vec3(1.0, 0.0, 0.0), M_PI / 2);
        $v = new Vec3(0.0, 1.0, 0.0);
        $result = $q->rotateVec3($v);
        $this->assertEqualsWithDelta(0.0, $result->x, 1e-6);
        $this->assertEqualsWithDelta(0.0, $result->y, 1e-6);
        $this->assertEqualsWithDelta(1.0, $result->z, 1e-6);
    }

    public function testMultiplyTwoY90EqualsY180(): void
    {
        $q90 = Quaternion::fromAxisAngle(new Vec3(0.0, 1.0, 0.0), M_PI / 2);
        $q180 = $q90->multiply($q90);
        $v = new Vec3(1.0, 0.0, 0.0);
        $result = $q180->rotateVec3($v);
        $this->assertEqualsWithDelta(-1.0, $result->x, 1e-5);
        $this->assertEqualsWithDelta(0.0, $result->y, 1e-5);
        $this->assertEqualsWithDelta(0.0, $result->z, 1e-5);
    }

    public function testNormalizeReturnsUnitLength(): void
    {
        $q = new Quaternion(1.0, 2.0, 3.0, 4.0);
        $n = $q->normalize();
        $this->assertEqualsWithDelta(1.0, $n->length(), 1e-6);
    }

    public function testConjugateTimesQEqualsIdentity(): void
    {
        $q = Quaternion::fromAxisAngle(new Vec3(1.0, 0.0, 0.0), 0.5)->normalize();
        $product = $q->multiply($q->conjugate());
        $this->assertEqualsWithDelta(0.0, $product->x, 1e-6);
        $this->assertEqualsWithDelta(0.0, $product->y, 1e-6);
        $this->assertEqualsWithDelta(0.0, $product->z, 1e-6);
        $this->assertEqualsWithDelta(1.0, $product->w, 1e-6);
    }

    public function testInverseOfIdentityIsIdentity(): void
    {
        $q = Quaternion::identity();
        $inv = $q->inverse();
        $this->assertTrue($q->equals($inv));
    }

    public function testSlerpHalfwayIs45Degrees(): void
    {
        $identity = Quaternion::identity();
        $q90 = Quaternion::fromAxisAngle(new Vec3(0.0, 1.0, 0.0), M_PI / 2);
        $q45 = $identity->slerp($q90, 0.5);

        $v = new Vec3(1.0, 0.0, 0.0);
        $result = $q45->rotateVec3($v);

        // 45-degree rotation around Y: (cos45, 0, -sin45)
        $this->assertEqualsWithDelta(cos(M_PI / 4), $result->x, 1e-5);
        $this->assertEqualsWithDelta(0.0, $result->y, 1e-5);
        $this->assertEqualsWithDelta(-sin(M_PI / 4), $result->z, 1e-5);
    }

    public function testSlerpT0ReturnsStart(): void
    {
        $a = Quaternion::identity();
        $b = Quaternion::fromAxisAngle(new Vec3(0.0, 1.0, 0.0), M_PI / 2);
        $result = $a->slerp($b, 0.0);
        $this->assertEqualsWithDelta($a->x, $result->x, 1e-5);
        $this->assertEqualsWithDelta($a->y, $result->y, 1e-5);
        $this->assertEqualsWithDelta($a->z, $result->z, 1e-5);
        $this->assertEqualsWithDelta($a->w, $result->w, 1e-5);
    }

    public function testSlerpT1ReturnsEnd(): void
    {
        $a = Quaternion::identity();
        $b = Quaternion::fromAxisAngle(new Vec3(0.0, 1.0, 0.0), M_PI / 2);
        $result = $a->slerp($b, 1.0);
        $this->assertEqualsWithDelta($b->x, $result->x, 1e-5);
        $this->assertEqualsWithDelta($b->y, $result->y, 1e-5);
        $this->assertEqualsWithDelta($b->z, $result->z, 1e-5);
        $this->assertEqualsWithDelta($b->w, $result->w, 1e-5);
    }

    public function testToRotationMatrixFromRotationMatrixRoundTrip(): void
    {
        $q = Quaternion::fromAxisAngle((new Vec3(1.0, 1.0, 0.0))->normalize(), M_PI / 3)->normalize();
        $mat = $q->toRotationMatrix();
        $restored = Quaternion::fromRotationMatrix($mat)->normalize();

        // May be negated — both represent same rotation
        $dot = abs($q->dot($restored));
        $this->assertEqualsWithDelta(1.0, $dot, 1e-5);
    }

    public function testFromEulerRoundTrip(): void
    {
        $q = Quaternion::fromEuler(0.3, 0.5, 0.1)->normalize();
        $mat = $q->toRotationMatrix();
        $restored = Quaternion::fromRotationMatrix($mat)->normalize();

        $dot = abs($q->dot($restored));
        $this->assertEqualsWithDelta(1.0, $dot, 1e-5);
    }

    public function testEquals(): void
    {
        $a = Quaternion::identity();
        $b = Quaternion::identity();
        $c = new Quaternion(0.0, 0.0, 0.0, 0.5);
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testToArrayFromArray(): void
    {
        $q = new Quaternion(0.1, 0.2, 0.3, 0.9274);
        $arr = $q->toArray();
        $restored = Quaternion::fromArray($arr);
        $this->assertTrue($q->equals($restored));
    }
}
