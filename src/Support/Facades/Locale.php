<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/** @see \PHPolygon\Locale\LocaleManager */
class Locale extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'locale';
    }
}
