<?php

declare(strict_types=1);

namespace PHPolygon\Component;

use PHPolygon\ECS\AbstractComponent;
use PHPolygon\ECS\Attribute\Category;
use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Serializable;

#[Serializable]
#[Category('Rendering')]
class MeshRenderer extends AbstractComponent
{
    #[Property(editorHint: 'asset:mesh')]
    public string $meshId;

    #[Property(editorHint: 'asset:material')]
    public string $materialId;

    #[Property]
    public bool $castShadows;

    public function __construct(
        string $meshId = '',
        string $materialId = '',
        bool $castShadows = true,
    ) {
        $this->meshId = $meshId;
        $this->materialId = $materialId;
        $this->castShadows = $castShadows;
    }
}
