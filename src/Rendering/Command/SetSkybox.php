<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Command;

readonly class SetSkybox
{
    public function __construct(
        public string $cubemapId,
    ) {}
}
