<?php

declare(strict_types=1);

namespace PHPolygon\Component;

enum WeatherState: string
{
    case Clear = 'clear';
    case Cloudy = 'cloudy';
    case Rain = 'rain';
    case Snow = 'snow';
    case Storm = 'storm';
    case Fog = 'fog';
}
