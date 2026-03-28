<?php

declare(strict_types=1);

namespace PHPolygon\Math;

class Vec4
{
    public function __construct(
        public float $x = 0.0,
        public float $y = 0.0,
        public float $z = 0.0,
        public float $w = 1.0,
    ) {}

    public static function zero(): self
    {
        return new self(0.0, 0.0, 0.0, 0.0);
    }

    public static function one(): self
    {
        return new self(1.0, 1.0, 1.0, 1.0);
    }

    public static function fromVec3(Vec3 $v, float $w = 1.0): self
    {
        return new self($v->x, $v->y, $v->z, $w);
    }

    public function toVec3(): Vec3
    {
        return new Vec3($this->x, $this->y, $this->z);
    }

    public function add(Vec4 $other): self
    {
        return new self($this->x + $other->x, $this->y + $other->y, $this->z + $other->z, $this->w + $other->w);
    }

    public function sub(Vec4 $other): self
    {
        return new self($this->x - $other->x, $this->y - $other->y, $this->z - $other->z, $this->w - $other->w);
    }

    public function mul(float $scalar): self
    {
        return new self($this->x * $scalar, $this->y * $scalar, $this->z * $scalar, $this->w * $scalar);
    }

    public function div(float $scalar): self
    {
        return new self($this->x / $scalar, $this->y / $scalar, $this->z / $scalar, $this->w / $scalar);
    }

    public function length(): float
    {
        return sqrt($this->x * $this->x + $this->y * $this->y + $this->z * $this->z + $this->w * $this->w);
    }

    public function normalize(): self
    {
        $len = $this->length();
        if ($len < 1e-10) {
            return self::zero();
        }
        return $this->div($len);
    }

    public function dot(Vec4 $other): float
    {
        return $this->x * $other->x + $this->y * $other->y + $this->z * $other->z + $this->w * $other->w;
    }

    public function equals(Vec4 $other, float $epsilon = 1e-6): bool
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
        return "Vec4({$this->x}, {$this->y}, {$this->z}, {$this->w})";
    }
}
