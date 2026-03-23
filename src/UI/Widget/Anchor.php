<?php

declare(strict_types=1);

namespace PHPolygon\UI\Widget;

enum Anchor: string
{
    case TopLeft = 'top_left';
    case TopCenter = 'top_center';
    case TopRight = 'top_right';
    case CenterLeft = 'center_left';
    case Center = 'center';
    case CenterRight = 'center_right';
    case BottomLeft = 'bottom_left';
    case BottomCenter = 'bottom_center';
    case BottomRight = 'bottom_right';
    case Fill = 'fill';
}
