<?php

declare(strict_types=1);

namespace PHPolygon\UI\Widget;

use PHPolygon\Rendering\Renderer2DInterface;
use PHPolygon\UI\UIStyle;

/**
 * Invisible spacer. In a VBox/HBox with fillWidth/fillHeight, pushes siblings apart.
 */
class Spacer extends Widget
{
    public function __construct(float $width = 0.0, float $height = 0.0)
    {
        parent::__construct();
        if ($width > 0 || $height > 0) {
            $this->sizing = Sizing::fixed($width, $height);
        } else {
            $this->sizing = Sizing::fill();
        }
    }

    public function measure(float $availableWidth, float $availableHeight, UIStyle $style): void
    {
        $this->measuredWidth = $this->sizing->fillWidth ? $availableWidth : $this->sizing->width;
        $this->measuredHeight = $this->sizing->fillHeight ? $availableHeight : $this->sizing->height;
    }

    public function layout(UIStyle $style): void {}

    public function draw(Renderer2DInterface $renderer, UIStyle $style): void
    {
        // Invisible
    }
}
