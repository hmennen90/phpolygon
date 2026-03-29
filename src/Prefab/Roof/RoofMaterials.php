<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Roof;

class RoofMaterials
{
    public readonly string $panelBack;
    public readonly string $ridge;
    public readonly string $rafter;
    public readonly string $gable;

    public function __construct(
        public readonly string $panel,
        ?string $panelBack = null,
        ?string $ridge = null,
        ?string $rafter = null,
        ?string $gable = null,
    ) {
        $this->panelBack = $panelBack ?? $panel;
        $this->ridge = $ridge ?? $panel;
        $this->rafter = $rafter ?? $this->ridge;
        $this->gable = $gable ?? $panel;
    }
}
