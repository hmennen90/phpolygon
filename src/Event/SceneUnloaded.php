<?php

declare(strict_types=1);

namespace PHPolygon\Event;

class SceneUnloaded
{
    public function __construct(
        public readonly string $sceneName,
    ) {}
}
