<?php

declare(strict_types=1);

namespace PHPolygon\UI\Widget;

use PHPolygon\Rendering\Renderer2DInterface;
use PHPolygon\UI\UIStyle;

class Separator extends Widget
{
    public float $thickness;

    public function __construct(float $thickness = 1.0)
    {
        parent::__construct();
        $this->thickness = $thickness;
        $this->margin = EdgeInsets::symmetric(vertical: 4.0);
    }

    public function measure(float $availableWidth, float $availableHeight, UIStyle $style): void
    {
        $this->measuredWidth = $this->sizing->fillWidth ? $availableWidth : ($this->sizing->width > 0 ? $this->sizing->width : $availableWidth);
        $this->measuredHeight = $this->thickness;
    }

    public function layout(UIStyle $style): void {}

    public function draw(Renderer2DInterface $renderer, UIStyle $style): void
    {
        $style = $this->resolveStyle($style);
        $b = $this->bounds;
        $renderer->drawRect($b->x, $b->y, $b->width, $this->thickness, $style->borderColor);
    }
}
