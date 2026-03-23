<?php

declare(strict_types=1);

namespace PHPolygon\Event;

class EntityDestroyed
{
    public function __construct(public readonly int $entityId) {}
}
