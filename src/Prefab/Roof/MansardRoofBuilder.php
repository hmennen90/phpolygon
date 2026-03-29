<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Roof;

use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

/**
 * Mansarddach — steep lower slope + shallow upper slope.
 * Creates more usable attic space. The break point is at ~60% of the roof height.
 */
class MansardRoofBuilder extends AbstractRoofBuilder
{
    private float $breakRatio;

    public function __construct(
        float $width,
        float $depth,
        float $roofHeight,
        float $overhang = 0.4,
        float $breakRatio = 0.6,
        int $rafterCount = 3,
        float $panelThickness = 0.08,
    ) {
        $this->width = $width;
        $this->depth = $depth;
        $this->roofHeight = $roofHeight;
        $this->overhang = $overhang;
        $this->breakRatio = $breakRatio;
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
        $entityCount = 0;

        // Lower section: steep slope (from eave to break point)
        $lowerH = $this->roofHeight * $this->breakRatio;
        $lowerSpan = ($this->depth * 0.5 + $this->overhang) * 0.35; // narrow span, steep
        $breakY = $lowerH;

        // Upper section: shallow slope (from break point to ridge)
        $upperH = $this->roofHeight - $lowerH;
        $upperSpan = $this->depth * 0.5 + $this->overhang - $lowerSpan;

        // Lower front panel
        $this->buildPanel(
            $builder, '_RoofLowerFront', $basePosition, $baseRotation,
            span: $lowerSpan, rise: $lowerH,
            panelWidth: $panelWidth, zOffset: ($this->depth * 0.5 + $this->overhang) - $lowerSpan * 0.5,
            flipTilt: false, materialId: $materials->panel,
        );
        // Lower back panel
        $this->buildPanel(
            $builder, '_RoofLowerBack', $basePosition, $baseRotation,
            span: $lowerSpan, rise: $lowerH,
            panelWidth: $panelWidth, zOffset: -($this->depth * 0.5 + $this->overhang) + $lowerSpan * 0.5,
            flipTilt: true, materialId: $materials->panel,
        );
        $entityCount += 2;

        // Upper panels (from break height)
        $upperBase = new Vec3($basePosition->x, $basePosition->y + $lowerH, $basePosition->z);
        $this->buildPanel(
            $builder, '_RoofUpperFront',
            $upperBase, $baseRotation,
            span: $upperSpan, rise: $upperH,
            panelWidth: $panelWidth, zOffset: $upperSpan * 0.5,
            flipTilt: false, materialId: $materials->panelBack,
        );
        $this->buildPanel(
            $builder, '_RoofUpperBack',
            $upperBase, $baseRotation,
            span: $upperSpan, rise: $upperH,
            panelWidth: $panelWidth, zOffset: -$upperSpan * 0.5,
            flipTilt: true, materialId: $materials->panelBack,
        );
        $entityCount += 2;

        // Ridge beam
        $this->buildRidgeBeam($builder, $basePosition, $baseRotation, $panelWidth, $materials->ridge);
        $entityCount++;

        // Gable walls (full span including overhang)
        $wallT = 0.1;
        $fullHalfSpan = $this->depth * 0.5 + $this->overhang;
        $this->buildGableWall($builder, '_GableL', $basePosition, $baseRotation,
            -$this->width * 0.5, $wallT, $materials->gable, $fullHalfSpan, $fullHalfSpan);
        $this->buildGableWall($builder, '_GableR', $basePosition, $baseRotation,
            $this->width * 0.5, $wallT, $materials->gable, $fullHalfSpan, $fullHalfSpan);
        $entityCount += 4;

        return new RoofResult(
            ridgeY: $basePosition->y + $this->roofHeight,
            eaveY: $basePosition->y,
            entityCount: $entityCount,
        );
    }
}
