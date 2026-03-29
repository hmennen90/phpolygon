<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Roof;

enum RoofType: string
{
    case Gable = 'gable';         // Satteldach
    case Hip = 'hip';             // Walmdach
    case Flat = 'flat';           // Flachdach
    case Shed = 'shed';           // Pultdach
    case Thatched = 'thatched';   // Strohdach (asymmetrisch)
    case Mansard = 'mansard';     // Mansarddach
}
