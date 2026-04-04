<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static void setPosition(float $x, float $y)
 * @method static void setZoom(float $zoom)
 * @method static float getZoom()
 *
 * @see \PHPolygon\Rendering\Camera2D
 */
class Camera2D extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'camera2D';
    }
}
