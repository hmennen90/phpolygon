<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Furniture;

use PHPolygon\Component\BodyType;
use PHPolygon\Component\BoxCollider3D;
use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\RigidBody3D;
use PHPolygon\Component\Transform3D;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

class CrateBuilder
{
    private string $prefix = '';
    private float $width;
    private float $height;
    private float $depth;
    private bool $dynamic = false;
    private float $mass = 5.0;

    private function __construct(float $width, float $height, float $depth)
    {
        $this->width = $width;
        $this->height = $height;
        $this->depth = $depth;
    }

    public static function wooden(float $width = 0.4, float $height = 0.3, float $depth = 0.4): self
    {
        return new self($width, $height, $depth);
    }

    /**
     * Make the crate a dynamic rigid body that responds to gravity and can be pushed.
     */
    public function dynamic(float $mass = 5.0): self
    {
        $this->dynamic = true;
        $this->mass = $mass;
        return $this;
    }

    public function withPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function build(SceneBuilder $builder, Vec3 $position, Quaternion $rotation, FurnitureMaterials $materials): FurnitureResult
    {
        $p = $this->prefix;

        $entity = $builder->entity("{$p}_Crate")
            ->with(new Transform3D(
                position: $position,
                rotation: $rotation,
                scale: new Vec3($this->width * 0.5, $this->height * 0.5, $this->depth * 0.5),
            ))
            ->with(new MeshRenderer(meshId: 'box', materialId: $materials->primary))
            ->with(new BoxCollider3D(size: new Vec3(2.0, 2.0, 2.0), isStatic: !$this->dynamic));

        if ($this->dynamic) {
            $entity->with(new RigidBody3D(
                bodyType: BodyType::Dynamic,
                mass: $this->mass,
                restitution: 0.1,
                friction: 0.6,
            ));
        }

        return new FurnitureResult(1, ["{$p}_Crate"]);
    }
}
