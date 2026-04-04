<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/** @see \PHPolygon\Rendering\RenderCommandList */
class CommandList3D extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'commandList3D';
    }
}
