<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static void listen(string $event, callable $listener)
 * @method static void dispatch(object $event)
 *
 * @see \PHPolygon\Event\EventDispatcher
 */
class Events extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'events';
    }
}
