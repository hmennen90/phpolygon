<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static void beginFrame()
 * @method static void endFrame()
 * @method static void clear()
 * @method static void setViewport(int $x, int $y, int $width, int $height)
 *
 * @see \PHPolygon\Rendering\Renderer3DInterface
 */
class Renderer3D extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'renderer3D';
    }
}
