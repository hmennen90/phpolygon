<?php

declare(strict_types=1);

namespace PHPolygon\Scene;

use PHPolygon\Engine;

abstract class Scene
{
    abstract public function getName(): string;

    abstract public function build(SceneBuilder $builder): void;

    /**
     * @return list<class-string<\PHPolygon\ECS\SystemInterface>>
     */
    public function getSystems(): array
    {
        return [];
    }

    public function getConfig(): SceneConfig
    {
        return new SceneConfig();
    }

    public function onLoad(Engine $engine): void {}

    public function onUnload(Engine $engine): void {}

    public function onActivate(Engine $engine): void {}

    public function onDeactivate(Engine $engine): void {}
}
