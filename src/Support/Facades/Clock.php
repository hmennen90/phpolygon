<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static float getDeltaTime()
 * @method static float getElapsedTime()
 * @method static int getFps()
 *
 * @see \PHPolygon\Runtime\Clock
 */
class Clock extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'clock';
    }
}
