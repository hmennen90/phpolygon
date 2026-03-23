<?php

declare(strict_types=1);

namespace PHPolygon\Event;

use PHPolygon\Scene\Scene;

class SceneUnloading
{
    public function __construct(
        public readonly string $sceneName,
        public readonly Scene $scene,
    ) {}
}
