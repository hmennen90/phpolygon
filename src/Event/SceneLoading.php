<?php

declare(strict_types=1);

namespace PHPolygon\Event;

class SceneLoading
{
    public function __construct(
        public readonly string $sceneName,
    ) {}
}
