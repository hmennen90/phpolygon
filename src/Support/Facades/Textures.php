<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static \PHPolygon\Rendering\Texture load(string $id, string $path)
 * @method static \PHPolygon\Rendering\Texture get(string $id)
 * @method static bool has(string $id)
 * @method static void clear()
 *
 * @see \PHPolygon\Rendering\TextureManager
 */
class Textures extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'textures';
    }
}
