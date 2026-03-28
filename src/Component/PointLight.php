<?php

declare(strict_types=1);

namespace PHPolygon\Component;

use PHPolygon\ECS\AbstractComponent;
use PHPolygon\ECS\Attribute\Category;
use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Range;
use PHPolygon\ECS\Attribute\Serializable;
use PHPolygon\Rendering\Color;

#[Serializable]
#[Category('Lighting')]
class PointLight extends AbstractComponent
{
    #[Property(editorHint: 'color')]
    public Color $color;

    #[Property(editorHint: 'slider')]
    #[Range(min: 0, max: 10)]
    public float $intensity;

    #[Property]
    public float $radius;

    public function __construct(
        ?Color $color = null,
        float $intensity = 1.0,
        float $radius = 10.0,
    ) {
        $this->color = $color ?? Color::white();
        $this->intensity = $intensity;
        $this->radius = $radius;
    }
}
