<?php

declare(strict_types=1);

namespace PHPolygon\UI\Widget;

use PHPolygon\Math\Rect;
use PHPolygon\Rendering\Renderer2DInterface;
use PHPolygon\UI\UIStyle;

/**
 * Horizontal box layout — stacks children left to right.
 */
class HBox extends Widget
{
    public float $spacing = 4.0;

    public function __construct(float $spacing = 4.0)
    {
        parent::__construct();
        $this->spacing = $spacing;
    }

    public function measure(float $availableWidth, float $availableHeight, UIStyle $style): void
    {
        $style = $this->resolveStyle($style);
        $contentW = $availableWidth - $this->padding->horizontal();
        $contentH = $availableHeight - $this->padding->vertical();

        $totalWidth = 0.0;
        $maxHeight = 0.0;
        $fillCount = 0;

        foreach ($this->children as $i => $child) {
            if (!$child->visible) continue;

            if ($child->sizing->fillWidth) {
                $fillCount++;
            } else {
                $child->measure($contentW, $contentH, $style);
                $totalWidth += $child->getMeasuredWidth() + $child->margin->horizontal();
            }
            if ($i > 0) $totalWidth += $this->spacing;
        }

        if ($fillCount > 0) {
            $remaining = max(0.0, $contentW - $totalWidth);
            $perChild = $remaining / $fillCount;
            foreach ($this->children as $child) {
                if (!$child->visible || !$child->sizing->fillWidth) continue;
                $child->measure($perChild - $child->margin->horizontal(), $contentH, $style);
                $totalWidth += $child->getMeasuredWidth() + $child->margin->horizontal();
            }
        }

        foreach ($this->children as $child) {
            if (!$child->visible) continue;
            $childH = $child->getMeasuredHeight() + $child->margin->vertical();
            if ($childH > $maxHeight) $maxHeight = $childH;
        }

        $this->measuredWidth = $this->resolveSize($this->sizing->fillWidth, $this->sizing->width,
            $totalWidth + $this->padding->horizontal(), $availableWidth,
            $this->sizing->minWidth, $this->sizing->maxWidth);
        $this->measuredHeight = $this->resolveSize($this->sizing->fillHeight, $this->sizing->height,
            $maxHeight + $this->padding->vertical(), $availableHeight,
            $this->sizing->minHeight, $this->sizing->maxHeight);
    }

    public function layout(UIStyle $style): void
    {
        $style = $this->resolveStyle($style);
        $content = $this->contentRect();
        $x = $content->x;

        foreach ($this->children as $child) {
            if (!$child->visible) continue;

            $childH = $child->sizing->fillHeight
                ? $content->height - $child->margin->vertical()
                : $child->getMeasuredHeight();

            $childX = $x + $child->margin->left;
            $childY = $content->y + $child->margin->top;

            $child->setBounds(new Rect($childX, $childY, $child->getMeasuredWidth(), $childH));
            $child->layout($style);

            $x = $childX + $child->getMeasuredWidth() + $child->margin->right + $this->spacing;
        }
    }

    public function draw(Renderer2DInterface $renderer, UIStyle $style): void
    {
        $style = $this->resolveStyle($style);
        foreach ($this->children as $child) {
            if (!$child->visible) continue;
            $child->draw($renderer, $style);
        }
    }

    private function resolveSize(bool $fill, float $fixed, float $content, float $available, float $min, float $max): float
    {
        if ($fill) return $available;
        if ($fixed > 0) return $fixed;
        return max($min, min($max, $content));
    }
}
