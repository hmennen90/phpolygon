<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

use PHPolygon\Engine;
use RuntimeException;

abstract class Facade
{
    private static ?Engine $engine = null;

    public static function setEngine(Engine $engine): void
    {
        self::$engine = $engine;
    }

    public static function getEngine(): Engine
    {
        if (self::$engine === null) {
            throw new RuntimeException(
                'Facade engine not set. Call Facade::setEngine($engine) or use $engine->run() which sets it automatically.',
            );
        }

        return self::$engine;
    }

    public static function clearEngine(): void
    {
        self::$engine = null;
    }

    abstract protected static function getFacadeAccessor(): string;

    protected static function resolveInstance(): object
    {
        $engine = self::getEngine();
        $accessor = static::getFacadeAccessor();

        /** @var object */
        return $engine->{$accessor};
    }

    /** @param array<mixed> $args */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::resolveInstance();

        return $instance->{$method}(...$args);
    }
}
