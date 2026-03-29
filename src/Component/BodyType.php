<?php

declare(strict_types=1);

namespace PHPolygon\Component;

enum BodyType: string
{
    case Static = 'static';       // Never moves, infinite mass
    case Kinematic = 'kinematic'; // Moved by code (doors, platforms), infinite mass, pushes dynamic
    case Dynamic = 'dynamic';     // Moved by physics (gravity, impulses, collisions)
}
