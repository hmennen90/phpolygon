<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Math;

use PHPUnit\Framework\TestCase;
use PHPolygon\Math\Mat4;
use PHPolygon\Math\Vec3;
use PHPolygon\Math\Vec4;

class Mat4Test extends TestCase
{
    public function testIdentityTransformsPointUnchanged(): void
    {
        $m = Mat4::identity();
        $p = new Vec3(1.0, 2.0, 3.0);
        $result = $m->transformPoint($p);
        $this->assertEqualsWithDelta($p->x, $result->x, 1e-6);
        $this->assertEqualsWithDelta($p->y, $result->y, 1e-6);
        $this->assertEqualsWithDelta($p->z, $result->z, 1e-6);
    }

    public function testTranslation(): void
    {
        $m = Mat4::translation(5.0, 10.0, 15.0);
        $p = new Vec3(1.0, 2.0, 3.0);
        $result = $m->transformPoint($p);
        $this->assertEqualsWithDelta(6.0, $result->x, 1e-6);
        $this->assertEqualsWithDelta(12.0, $result->y, 1e-6);
        $this->assertEqualsWithDelta(18.0, $result->z, 1e-6);
    }

    public function testScaling(): void
    {
        $m = Mat4::scaling(2.0, 3.0, 4.0);
        $p = new Vec3(1.0, 2.0, 3.0);
        $result = $m->transformPoint($p);
        $this->assertEqualsWithDelta(2.0, $result->x, 1e-6);
        $this->assertEqualsWithDelta(6.0, $result->y, 1e-6);
        $this->assertEqualsWithDelta(12.0, $result->z, 1e-6);
    }

    public function testRotationX90(): void
    {
        $m = Mat4::rotationX(M_PI / 2);
        $p = new Vec3(0.0, 1.0, 0.0);
        $result = $m->transformPoint($p);
        $this->assertEqualsWithDelta(0.0, $result->x, 1e-6);
        $this->assertEqualsWithDelta(0.0, $result->y, 1e-6);
        $this->assertEqualsWithDelta(1.0, $result->z, 1e-6);
    }

    public function testRotationY90(): void
    {
        $m = Mat4::rotationY(M_PI / 2);
        $p = new Vec3(1.0, 0.0, 0.0);
        $result = $m->transformPoint($p);
        $this->assertEqualsWithDelta(0.0, $result->x, 1e-6);
        $this->assertEqualsWithDelta(0.0, $result->y, 1e-6);
        $this->assertEqualsWithDelta(-1.0, $result->z, 1e-6);
    }

    public function testRotationZ90(): void
    {
        $m = Mat4::rotationZ(M_PI / 2);
        $p = new Vec3(1.0, 0.0, 0.0);
        $result = $m->transformPoint($p);
        $this->assertEqualsWithDelta(0.0, $result->x, 1e-6);
        $this->assertEqualsWithDelta(1.0, $result->y, 1e-6);
        $this->assertEqualsWithDelta(0.0, $result->z, 1e-6);
    }

    public function testLookAt(): void
    {
        $eye    = new Vec3(0.0, 0.0, 5.0);
        $center = new Vec3(0.0, 0.0, 0.0);
        $up     = new Vec3(0.0, 1.0, 0.0);
        $view   = Mat4::lookAt($eye, $center, $up);

        // The origin should map to (0, 0, -5) in view space
        $result = $view->transformPoint(new Vec3(0.0, 0.0, 0.0));
        $this->assertEqualsWithDelta(0.0, $result->x, 1e-5);
        $this->assertEqualsWithDelta(0.0, $result->y, 1e-5);
        $this->assertEqualsWithDelta(-5.0, $result->z, 1e-5);
    }

    public function testPerspectiveNearMapsToMinusOne(): void
    {
        $near = 0.1;
        $far  = 100.0;
        $proj = Mat4::perspective(M_PI / 2, 1.0, $near, $far);

        // A point exactly at the near plane center: (0, 0, -near)
        $v = $proj->multiplyVec4(new Vec4(0.0, 0.0, -$near, 1.0));
        $ndcZ = $v->z / $v->w;
        $this->assertEqualsWithDelta(-1.0, $ndcZ, 1e-5);
    }

    public function testPerspectiveFarMapsToOne(): void
    {
        $near = 0.1;
        $far  = 100.0;
        $proj = Mat4::perspective(M_PI / 2, 1.0, $near, $far);

        $v = $proj->multiplyVec4(new Vec4(0.0, 0.0, -$far, 1.0));
        $ndcZ = $v->z / $v->w;
        $this->assertEqualsWithDelta(1.0, $ndcZ, 1e-5);
    }

    public function testMultiply(): void
    {
        $t = Mat4::translation(5.0, 0.0, 0.0);
        $s = Mat4::scaling(2.0, 2.0, 2.0);
        // scale then translate: point (1,0,0) -> scale -> (2,0,0) -> translate -> (7,0,0)
        $m = $t->multiply($s);
        $result = $m->transformPoint(new Vec3(1.0, 0.0, 0.0));
        $this->assertEqualsWithDelta(7.0, $result->x, 1e-6);
        $this->assertEqualsWithDelta(0.0, $result->y, 1e-6);
        $this->assertEqualsWithDelta(0.0, $result->z, 1e-6);
    }

    public function testMultiplyVec4(): void
    {
        $m = Mat4::translation(1.0, 2.0, 3.0);
        $v = new Vec4(0.0, 0.0, 0.0, 1.0);
        $result = $m->multiplyVec4($v);
        $this->assertEqualsWithDelta(1.0, $result->x, 1e-6);
        $this->assertEqualsWithDelta(2.0, $result->y, 1e-6);
        $this->assertEqualsWithDelta(3.0, $result->z, 1e-6);
        $this->assertEqualsWithDelta(1.0, $result->w, 1e-6);
    }

    public function testTransformDirection(): void
    {
        // Translation must NOT affect direction vectors (w=0)
        $m = Mat4::translation(100.0, 200.0, 300.0);
        $dir = new Vec3(1.0, 0.0, 0.0);
        $result = $m->transformDirection($dir);
        $this->assertEqualsWithDelta(1.0, $result->x, 1e-6);
        $this->assertEqualsWithDelta(0.0, $result->y, 1e-6);
        $this->assertEqualsWithDelta(0.0, $result->z, 1e-6);
    }

    public function testTransposeOfIdentityIsIdentity(): void
    {
        $m = Mat4::identity();
        $t = $m->transpose();
        for ($i = 0; $i < 16; $i++) {
            $this->assertEqualsWithDelta($m->toArray()[$i], $t->toArray()[$i], 1e-6);
        }
    }

    public function testDoubleTransposeEqualsOriginal(): void
    {
        $m = Mat4::translation(1.0, 2.0, 3.0);
        $tt = $m->transpose()->transpose();
        for ($i = 0; $i < 16; $i++) {
            $this->assertEqualsWithDelta($m->toArray()[$i], $tt->toArray()[$i], 1e-6);
        }
    }

    public function testInverse(): void
    {
        $m = Mat4::translation(3.0, 5.0, 7.0);
        $inv = $m->inverse();
        $product = $m->multiply($inv);
        $identity = Mat4::identity();
        for ($i = 0; $i < 16; $i++) {
            $this->assertEqualsWithDelta($identity->toArray()[$i], $product->toArray()[$i], 1e-5);
        }
    }

    public function testInverseScaling(): void
    {
        $m = Mat4::scaling(2.0, 3.0, 4.0);
        $inv = $m->inverse();
        $product = $m->multiply($inv);
        $identity = Mat4::identity();
        for ($i = 0; $i < 16; $i++) {
            $this->assertEqualsWithDelta($identity->toArray()[$i], $product->toArray()[$i], 1e-5);
        }
    }

    public function testGet(): void
    {
        $m = Mat4::translation(1.0, 2.0, 3.0);
        // Translation is in column 3 (index 12-15)
        $this->assertEqualsWithDelta(1.0, $m->get(0, 3), 1e-6);
        $this->assertEqualsWithDelta(2.0, $m->get(1, 3), 1e-6);
        $this->assertEqualsWithDelta(3.0, $m->get(2, 3), 1e-6);
        $this->assertEqualsWithDelta(1.0, $m->get(3, 3), 1e-6);
    }

    public function testGetTranslation(): void
    {
        $m = Mat4::translation(7.0, 8.0, 9.0);
        $t = $m->getTranslation();
        $this->assertEqualsWithDelta(7.0, $t->x, 1e-6);
        $this->assertEqualsWithDelta(8.0, $t->y, 1e-6);
        $this->assertEqualsWithDelta(9.0, $t->z, 1e-6);
    }

    public function testToArray(): void
    {
        $m = Mat4::identity();
        $arr = $m->toArray();
        $this->assertCount(16, $arr);
        $this->assertEqualsWithDelta(1.0, $arr[0], 1e-6);
        $this->assertEqualsWithDelta(0.0, $arr[1], 1e-6);
    }
}
