<?php

declare(strict_types=1);

namespace PHPolygon\Audio;

enum AudioChannel: string
{
    case Master = 'master';
    case Music = 'music';
    case SFX = 'sfx';
    case UI = 'ui';
    case Voice = 'voice';
}
