<?php

declare(strict_types=1);

namespace PHPolygon\Scene;

abstract class AbstractPrefab implements PrefabInterface
{
    abstract public static function getName(): string;

    abstract public function build(SceneBuilder $builder): EntityDeclaration;
}
