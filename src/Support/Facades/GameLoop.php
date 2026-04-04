<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static float getFixedDeltaTime()
 *
 * @see \PHPolygon\Runtime\GameLoop
 */
class GameLoop extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'gameLoop';
    }
}
