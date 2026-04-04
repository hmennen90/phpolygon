<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static bool shouldClose()
 * @method static void setFullscreen()
 * @method static void setBorderless()
 * @method static void setWindowed()
 * @method static void toggleFullscreen()
 * @method static int getFramebufferWidth()
 * @method static int getFramebufferHeight()
 * @method static void destroy()
 *
 * @see \PHPolygon\Runtime\Window
 */
class Window extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'window';
    }
}
