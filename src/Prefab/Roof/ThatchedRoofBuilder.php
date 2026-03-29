<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Roof;

use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

/**
 * Strohdach — asymmetric gable with different front/back spans.
 * The front panel can extend further (e.g., over a porch).
 * Thicker panels for a thatched appearance.
 */
class ThatchedRoofBuilder extends AbstractRoofBuilder
{
    private float $frontExtension;

    public function __construct(
        float $width,
        float $depth,
        float $roofHeight,
        float $overhang = 0.7,
        float $frontExtension = 0.0,
        int $rafterCount = 4,
        float $panelThickness = 0.12,
    ) {
        $this->width = $width;
        $this->depth = $depth;
        $this->roofHeight = $roofHeight;
        $this->overhang = $overhang;
        $this->frontExtension = $frontExtension;
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
        $frontSpan = $this->depth * 0.5 + $this->frontExtension + $this->overhang;
        $backSpan = $this->depth * 0.5 + $this->overhang;
        $entityCount = 0;

        // Front panel (longer, covers porch)
        $this->buildPanel(
            $builder, '_RoofFront', $basePosition, $baseRotation,
            span: $frontSpan, rise: $this->roofHeight,
            panelWidth: $panelWidth, zOffset: $frontSpan * 0.5,
            flipTilt: false, materialId: $materials->panel,
        );
        $entityCount++;

        // Back panel (shorter)
        $this->buildPanel(
            $builder, '_RoofBack', $basePosition, $baseRotation,
            span: $backSpan, rise: $this->roofHeight,
            panelWidth: $panelWidth, zOffset: -$backSpan * 0.5,
            flipTilt: true, materialId: $materials->panelBack,
        );
        $entityCount++;

        // Ridge beam
        $this->buildRidgeBeam($builder, $basePosition, $baseRotation, $panelWidth, $materials->ridge);
        $entityCount++;

        // Rafters
        $entityCount += $this->buildRafters(
            $builder, 'F', $basePosition, $baseRotation,
            span: $frontSpan, rise: $this->roofHeight,
            panelWidth: $panelWidth, zOffset: $frontSpan * 0.5,
            flipTilt: false, materialId: $materials->rafter,
        );
        $entityCount += $this->buildRafters(
            $builder, 'B', $basePosition, $baseRotation,
            span: $backSpan, rise: $this->roofHeight,
            panelWidth: $panelWidth, zOffset: -$backSpan * 0.5,
            flipTilt: true, materialId: $materials->rafter,
        );

        // Gable walls (single wedge per side, scaled to larger span)
        $wallT = 0.1;
        $this->buildGableWall($builder, '_GableL', $basePosition, $baseRotation,
            -$this->width * 0.5, $wallT, $materials->gable, $frontSpan, $backSpan);
        $this->buildGableWall($builder, '_GableR', $basePosition, $baseRotation,
            $this->width * 0.5, $wallT, $materials->gable, $frontSpan, $backSpan);
        $entityCount += 4;

        // Calculate roof underside Y at the front/back wall positions.
        // Front wall is at Z = depth/2 from center. Roof drops roofHeight over frontSpan.
        $halfDepth = $this->depth * 0.5;
        $frontWallRoofY = $basePosition->y + $this->roofHeight * (1.0 - $halfDepth / $frontSpan);
        $backWallRoofY = $basePosition->y + $this->roofHeight * (1.0 - $halfDepth / $backSpan);

        return new RoofResult(
            ridgeY: $basePosition->y + $this->roofHeight,
            eaveY: $basePosition->y,
            entityCount: $entityCount,
            frontWallTopY: $frontWallRoofY,
            backWallTopY: $backWallRoofY,
        );
    }
}
