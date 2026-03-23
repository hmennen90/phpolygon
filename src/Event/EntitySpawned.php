<?php

declare(strict_types=1);

namespace PHPolygon\Event;

use PHPolygon\ECS\Entity;

class EntitySpawned
{
    public function __construct(public readonly Entity $entity) {}
}
