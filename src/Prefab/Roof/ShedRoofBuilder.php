<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Roof;

use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

/**
 * Pultdach — single slope from high side to low side.
 * Gable walls on left and right fill the triangular gap.
 */
class ShedRoofBuilder extends AbstractRoofBuilder
{
    public function __construct(
        float $width,
        float $depth,
        float $roofHeight,
        float $overhang = 0.5,
        int $rafterCount = 4,
        float $panelThickness = 0.10,
    ) {
        $this->width = $width;
        $this->depth = $depth;
        $this->roofHeight = $roofHeight;
        $this->overhang = $overhang;
        $this->rafterCount = $rafterCount;
        $this->panelThickness = $panelThickness;
    }

    public function build(
        SceneBuilder $builder,
        Vec3 $basePosition,
        Quaternion $baseRotation,
        RoofMaterials $materials,
    ): RoofResult {
        $panelWidth = $this->width * 0.5 + $this->overhang;
        $fullSpan = $this->depth + $this->overhang * 2;
        $entityCount = 0;

        // Single sloped panel across the full depth
        $this->buildPanel(
            $builder, '_RoofShed', $basePosition, $baseRotation,
            span: $fullSpan, rise: $this->roofHeight,
            panelWidth: $panelWidth, zOffset: 0.0,
            flipTilt: false, materialId: $materials->panel,
        );
        $entityCount++;

        // Rafters
        $entityCount += $this->buildRafters(
            $builder, '', $basePosition, $baseRotation,
            span: $fullSpan, rise: $this->roofHeight,
            panelWidth: $panelWidth, zOffset: 0.0,
            flipTilt: false, materialId: $materials->rafter,
        );

        // Gable walls
        $wallT = 0.1;
        $halfSpan = $fullSpan * 0.5;
        $this->buildGableWall($builder, '_GableL', $basePosition, $baseRotation,
            -$this->width * 0.5, $wallT, $materials->gable, $halfSpan, $halfSpan);
        $this->buildGableWall($builder, '_GableR', $basePosition, $baseRotation,
            $this->width * 0.5, $wallT, $materials->gable, $halfSpan, $halfSpan);
        $entityCount += 4;

        return new RoofResult(
            ridgeY: $basePosition->y + $this->roofHeight,
            eaveY: $basePosition->y,
            entityCount: $entityCount,
        );
    }
}
