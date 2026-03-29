<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Door;

enum DoorType: string
{
    case Single = 'single';       // Einzeltuer (Scharnier)
    case Double = 'double';       // Doppeltuer (2 Fluegel)
    case Sliding = 'sliding';     // Schiebetuer
    case Trapdoor = 'trapdoor';   // Falltuer / Klappe
    case Revolving = 'revolving'; // Drehtuer
}
