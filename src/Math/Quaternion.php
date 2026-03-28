<?php

declare(strict_types=1);

namespace PHPolygon\Math;

/**
 * Unit quaternion for 3D rotation.
 * Stored as (x, y, z, w) — vector part first, scalar last (GLSL convention).
 */
class Quaternion
{
    public function __construct(
        public float $x = 0.0,
        public float $y = 0.0,
        public float $z = 0.0,
        public float $w = 1.0,
    ) {}

    public static function identity(): self
    {
        return new self(0.0, 0.0, 0.0, 1.0);
    }

    public static function fromAxisAngle(Vec3 $axis, float $radians): self
    {
        $half = $radians / 2.0;
        $s = sin($half);
        $n = $axis->normalize();
        return new self($n->x * $s, $n->y * $s, $n->z * $s, cos($half));
    }

    /**
     * From Euler angles in radians. Applied in YXZ order (yaw around Y, pitch around X, roll around Z).
     */
    public static function fromEuler(float $pitch, float $yaw, float $roll): self
    {
        $cp = cos($pitch / 2.0);
        $sp = sin($pitch / 2.0);
        $cy = cos($yaw / 2.0);
        $sy = sin($yaw / 2.0);
        $cr = cos($roll / 2.0);
        $sr = sin($roll / 2.0);

        return new self(
            $cy * $sp * $cr + $sy * $cp * $sr,
            $sy * $cp * $cr - $cy * $sp * $sr,
            $cy * $cp * $sr - $sy * $sp * $cr,
            $cy * $cp * $cr + $sy * $sp * $sr,
        );
    }

    /**
     * Extract rotation quaternion from a Mat4 rotation matrix using Shepperd's method.
     */
    public static function fromRotationMatrix(Mat4 $m): self
    {
        $arr = $m->toArray();
        // Column-major: m00=arr[0], m11=arr[5], m22=arr[10]
        $m00 = $arr[0];
        $m11 = $arr[5];
        $m22 = $arr[10];
        $m10 = $arr[1]; $m01 = $arr[4];
        $m20 = $arr[2]; $m02 = $arr[8];
        $m21 = $arr[6]; $m12 = $arr[9];

        $trace = $m00 + $m11 + $m22;

        if ($trace > 0.0) {
            $s = 0.5 / sqrt($trace + 1.0);
            return new self(
                ($m21 - $m12) * $s,
                ($m02 - $m20) * $s,
                ($m10 - $m01) * $s,
                0.25 / $s,
            );
        } elseif ($m00 > $m11 && $m00 > $m22) {
            $s = 2.0 * sqrt(1.0 + $m00 - $m11 - $m22);
            return new self(
                0.25 * $s,
                ($m01 + $m10) / $s,
                ($m02 + $m20) / $s,
                ($m21 - $m12) / $s,
            );
        } elseif ($m11 > $m22) {
            $s = 2.0 * sqrt(1.0 + $m11 - $m00 - $m22);
            return new self(
                ($m01 + $m10) / $s,
                0.25 * $s,
                ($m12 + $m21) / $s,
                ($m02 - $m20) / $s,
            );
        } else {
            $s = 2.0 * sqrt(1.0 + $m22 - $m00 - $m11);
            return new self(
                ($m02 + $m20) / $s,
                ($m12 + $m21) / $s,
                0.25 * $s,
                ($m10 - $m01) / $s,
            );
        }
    }

    /** Hamilton product: this * other */
    public function multiply(Quaternion $other): self
    {
        return new self(
            $this->w * $other->x + $this->x * $other->w + $this->y * $other->z - $this->z * $other->y,
            $this->w * $other->y - $this->x * $other->z + $this->y * $other->w + $this->z * $other->x,
            $this->w * $other->z + $this->x * $other->y - $this->y * $other->x + $this->z * $other->w,
            $this->w * $other->w - $this->x * $other->x - $this->y * $other->y - $this->z * $other->z,
        );
    }

    public function length(): float
    {
        return sqrt($this->x * $this->x + $this->y * $this->y + $this->z * $this->z + $this->w * $this->w);
    }

    public function normalize(): self
    {
        $len = $this->length();
        if ($len < 1e-10) {
            return self::identity();
        }
        return new self($this->x / $len, $this->y / $len, $this->z / $len, $this->w / $len);
    }

    public function conjugate(): self
    {
        return new self(-$this->x, -$this->y, -$this->z, $this->w);
    }

    public function inverse(): self
    {
        $lenSq = $this->x * $this->x + $this->y * $this->y + $this->z * $this->z + $this->w * $this->w;
        if ($lenSq < 1e-10) {
            return self::identity();
        }
        $inv = 1.0 / $lenSq;
        return new self(-$this->x * $inv, -$this->y * $inv, -$this->z * $inv, $this->w * $inv);
    }

    public function dot(Quaternion $other): float
    {
        return $this->x * $other->x + $this->y * $other->y + $this->z * $other->z + $this->w * $other->w;
    }

    public function slerp(Quaternion $target, float $t): self
    {
        $dot = $this->dot($target);

        // Handle dot < 0 by negating target to take the shorter path
        $tgt = $target;
        if ($dot < 0.0) {
            $tgt = new self(-$target->x, -$target->y, -$target->z, -$target->w);
            $dot = -$dot;
        }

        // If quaternions are very close, use linear interpolation to avoid division by zero
        if ($dot > 0.9995) {
            return (new self(
                $this->x + $t * ($tgt->x - $this->x),
                $this->y + $t * ($tgt->y - $this->y),
                $this->z + $t * ($tgt->z - $this->z),
                $this->w + $t * ($tgt->w - $this->w),
            ))->normalize();
        }

        $theta0 = acos($dot);
        $theta  = $theta0 * $t;
        $sinTheta0 = sin($theta0);
        $sinTheta  = sin($theta);

        $s0 = cos($theta) - $dot * $sinTheta / $sinTheta0;
        $s1 = $sinTheta / $sinTheta0;

        return new self(
            $s0 * $this->x + $s1 * $tgt->x,
            $s0 * $this->y + $s1 * $tgt->y,
            $s0 * $this->z + $s1 * $tgt->z,
            $s0 * $this->w + $s1 * $tgt->w,
        );
    }

    public function toRotationMatrix(): Mat4
    {
        $x = $this->x;
        $y = $this->y;
        $z = $this->z;
        $w = $this->w;

        $xx = $x * $x; $yy = $y * $y; $zz = $z * $z;
        $xy = $x * $y; $xz = $x * $z; $yz = $y * $z;
        $wx = $w * $x; $wy = $w * $y; $wz = $w * $z;

        return new Mat4([
            1.0 - 2.0*($yy + $zz), 2.0*($xy + $wz),       2.0*($xz - $wy),       0.0,
            2.0*($xy - $wz),       1.0 - 2.0*($xx + $zz), 2.0*($yz + $wx),       0.0,
            2.0*($xz + $wy),       2.0*($yz - $wx),       1.0 - 2.0*($xx + $yy), 0.0,
            0.0,                   0.0,                   0.0,                   1.0,
        ]);
    }

    public function rotateVec3(Vec3 $v): Vec3
    {
        // Sandwich product: q * v * q^-1
        $qv = new self($v->x, $v->y, $v->z, 0.0);
        $result = $this->multiply($qv)->multiply($this->conjugate());
        return new Vec3($result->x, $result->y, $result->z);
    }

    public function equals(Quaternion $other, float $epsilon = 1e-6): bool
    {
        return abs($this->x - $other->x) < $epsilon
            && abs($this->y - $other->y) < $epsilon
            && abs($this->z - $other->z) < $epsilon
            && abs($this->w - $other->w) < $epsilon;
    }

    /** @return array{x: float, y: float, z: float, w: float} */
    public function toArray(): array
    {
        return ['x' => $this->x, 'y' => $this->y, 'z' => $this->z, 'w' => $this->w];
    }

    /** @param array{x: float, y: float, z: float, w: float} $data */
    public static function fromArray(array $data): self
    {
        return new self((float)$data['x'], (float)$data['y'], (float)$data['z'], (float)$data['w']);
    }

    public function __toString(): string
    {
        return "Quaternion({$this->x}, {$this->y}, {$this->z}, {$this->w})";
    }
}
