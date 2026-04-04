<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/** @see \PHPolygon\ECS\World */
class World extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'world';
    }
}
