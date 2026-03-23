<?php

declare(strict_types=1);

namespace PHPolygon\Scene;

enum LoadMode: string
{
    case Single = 'single';
    case Additive = 'additive';
}
