<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Roof;

use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

/**
 * Satteldach — two sloped panels meeting at a central ridge.
 * Includes ridge beam, rafters, and gable walls on the sides.
 */
class GableRoofBuilder extends AbstractRoofBuilder
{
    public function __construct(
        float $width,
        float $depth,
        float $roofHeight,
        float $overhang = 0.5,
        int $rafterCount = 4,
        float $panelThickness = 0.12,
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
        $halfSpan = $this->depth * 0.5 + $this->overhang;
        $entityCount = 0;

        // Front panel (positive Z from base)
        $this->buildPanel(
            $builder, '_RoofFront', $basePosition, $baseRotation,
            span: $halfSpan,
            rise: $this->roofHeight,
            panelWidth: $panelWidth,
            zOffset: $halfSpan * 0.5,
            flipTilt: false,
            materialId: $materials->panel,
        );
        $entityCount++;

        // Back panel (negative Z from base)
        $this->buildPanel(
            $builder, '_RoofBack', $basePosition, $baseRotation,
            span: $halfSpan,
            rise: $this->roofHeight,
            panelWidth: $panelWidth,
            zOffset: -$halfSpan * 0.5,
            flipTilt: true,
            materialId: $materials->panelBack,
        );
        $entityCount++;

        // Ridge beam
        $this->buildRidgeBeam($builder, $basePosition, $baseRotation, $panelWidth, $materials->ridge);
        $entityCount++;

        // Rafters (front + back)
        $entityCount += $this->buildRafters(
            $builder, 'F', $basePosition, $baseRotation,
            span: $halfSpan, rise: $this->roofHeight,
            panelWidth: $panelWidth, zOffset: $halfSpan * 0.5,
            flipTilt: false, materialId: $materials->rafter,
        );
        $entityCount += $this->buildRafters(
            $builder, 'B', $basePosition, $baseRotation,
            span: $halfSpan, rise: $this->roofHeight,
            panelWidth: $panelWidth, zOffset: -$halfSpan * 0.5,
            flipTilt: true, materialId: $materials->rafter,
        );

        // Gable walls (left and right sides, slope matches roof span)
        $wallT = 0.1;
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
