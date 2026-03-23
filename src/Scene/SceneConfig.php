<?php

declare(strict_types=1);

namespace PHPolygon\Scene;

use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Serializable;
use PHPolygon\Math\Vec2;
use PHPolygon\Rendering\Color;

#[Serializable]
class SceneConfig
{
    #[Property(editorHint: 'color')]
    public Color $clearColor;

    #[Property(editorHint: 'vec2')]
    public Vec2 $gravity;

    #[Property]
    public float $timeScale;

    public function __construct(
        ?Color $clearColor = null,
        ?Vec2 $gravity = null,
        float $timeScale = 1.0,
    ) {
        $this->clearColor = $clearColor ?? Color::hex('#1a1a2e');
        $this->gravity = $gravity ?? new Vec2(0.0, 980.0);
        $this->timeScale = $timeScale;
    }
}
