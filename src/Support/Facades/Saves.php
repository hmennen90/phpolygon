<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/** @see \PHPolygon\SaveGame\SaveManager */
class Saves extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'saves';
    }
}
