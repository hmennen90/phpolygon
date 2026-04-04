<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static void register(string $name, string $sceneClass)
 * @method static void load(string $sceneClass)
 * @method static void unload()
 *
 * @see \PHPolygon\Scene\SceneManager
 * @see \PHPolygon\Scene\SceneManagerInterface
 */
class Scenes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'scenes';
    }
}
