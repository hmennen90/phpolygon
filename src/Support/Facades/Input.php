<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static bool isKeyPressed(int $key)
 * @method static bool isKeyDown(int $key)
 * @method static bool isKeyReleased(int $key)
 * @method static bool isMouseButtonPressed(int $button)
 * @method static bool isMouseButtonDown(int $button)
 * @method static bool isMouseButtonReleased(int $button)
 * @method static float getMouseX()
 * @method static float getMouseY()
 * @method static float getScrollX()
 * @method static float getScrollY()
 *
 * @see \PHPolygon\Runtime\Input
 * @see \PHPolygon\Runtime\InputInterface
 */
class Input extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'input';
    }
}
