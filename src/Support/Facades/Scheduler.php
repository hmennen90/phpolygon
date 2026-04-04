<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static void boot()
 * @method static void shutdown()
 *
 * @see \PHPolygon\Thread\ThreadScheduler
 * @see \PHPolygon\Thread\NullThreadScheduler
 */
class Scheduler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'scheduler';
    }
}
