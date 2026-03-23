<?php

declare(strict_types=1);

namespace PHPolygon\Scene;

interface PrefabInterface
{
    public static function getName(): string;

    public function build(SceneBuilder $builder): EntityDeclaration;
}
