<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Furniture;

use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\PointLight;
use PHPolygon\Component\Transform3D;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Rendering\Color;
use PHPolygon\Scene\SceneBuilder;

class LanternBuilder
{
    private string $prefix = '';
    private string $style;
    private float $ropeLength;
    private float $lanternSize;
    private float $lightIntensity;
    private float $lightRadius;
    private string $lightColor;

    private function __construct(string $style, float $ropeLength)
    {
        $this->style = $style;
        $this->ropeLength = $ropeLength;
        $this->lanternSize = 0.08;
        $this->lightIntensity = 1.5;
        $this->lightRadius = 6.0;
        $this->lightColor = '#FFCC66';
    }

    public static function hanging(float $ropeLength = 0.5): self
    {
        return new self('hanging', $ropeLength);
    }

    public static function standing(float $height = 0.3): self
    {
        $inst = new self('standing', 0.0);
        $inst->lanternSize = 0.1;
        return $inst;
    }

    public function withLight(float $intensity = 1.5, float $radius = 6.0, string $color = '#FFCC66'): self
    {
        $this->lightIntensity = $intensity;
        $this->lightRadius = $radius;
        $this->lightColor = $color;
        return $this;
    }

    public function withPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function build(SceneBuilder $builder, Vec3 $position, Quaternion $rotation, FurnitureMaterials $materials): FurnitureResult
    {
        $names = [];
        $p = $this->prefix;

        $lanternY = $position->y;

        if ($this->style === 'hanging') {
            // Rope from attachment point down
            $ropePos = new Vec3($position->x, $position->y + $this->ropeLength * 0.5, $position->z);
            $builder->entity("{$p}_LanternRope")
                ->with(new Transform3D(
                    position: $ropePos,
                    rotation: $rotation,
                    scale: new Vec3(0.008, $this->ropeLength * 0.5, 0.008),
                ))
                ->with(new MeshRenderer(meshId: 'cylinder', materialId: $materials->secondary));
            $names[] = "{$p}_LanternRope";

            $lanternY = $position->y - $this->ropeLength * 0.5;
        }

        // Lantern body
        $builder->entity("{$p}_Lantern")
            ->with(new Transform3D(
                position: new Vec3($position->x, $lanternY, $position->z),
                rotation: $rotation,
                scale: new Vec3($this->lanternSize, $this->lanternSize * 1.3, $this->lanternSize),
            ))
            ->with(new MeshRenderer(meshId: 'sphere', materialId: $materials->metal));
        $names[] = "{$p}_Lantern";

        // Point light
        $builder->entity("{$p}_LanternLight")
            ->with(new Transform3D(
                position: new Vec3($position->x, $lanternY, $position->z),
            ))
            ->with(new PointLight(
                color: Color::hex($this->lightColor),
                intensity: $this->lightIntensity,
                radius: $this->lightRadius,
            ));
        $names[] = "{$p}_LanternLight";

        return new FurnitureResult(count($names), $names);
    }
}
