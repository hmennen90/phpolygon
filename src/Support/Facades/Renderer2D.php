<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/** @see \PHPolygon\Rendering\Renderer2DInterface */
class Renderer2D extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'renderer2D';
    }
}
