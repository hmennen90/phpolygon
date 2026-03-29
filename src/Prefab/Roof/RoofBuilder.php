<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Roof;

/**
 * Static factory for creating roof builders.
 *
 * Usage:
 *   $roof = RoofBuilder::gable(width: 3.5, depth: 3.0, roofHeight: 1.2);
 *   $result = $roof->withPrefix('Hut')->build($builder, $basePos, $baseRot, $materials);
 */
class RoofBuilder
{
    /** Satteldach — two sloped panels meeting at a central ridge */
    public static function gable(
        float $width,
        float $depth,
        float $roofHeight,
        float $overhang = 0.5,
        int $rafterCount = 4,
        float $panelThickness = 0.12,
    ): GableRoofBuilder {
        return new GableRoofBuilder($width, $depth, $roofHeight, $overhang, $rafterCount, $panelThickness);
    }

    /** Walmdach — four sloped panels, no gable walls */
    public static function hip(
        float $width,
        float $depth,
        float $roofHeight,
        float $overhang = 0.5,
        int $rafterCount = 3,
        float $panelThickness = 0.10,
    ): HipRoofBuilder {
        return new HipRoofBuilder($width, $depth, $roofHeight, $overhang, $rafterCount, $panelThickness);
    }

    /** Flachdach — single horizontal panel with drainage slope */
    public static function flat(
        float $width,
        float $depth,
        float $overhang = 0.3,
        float $drainageAngle = 2.0,
        float $panelThickness = 0.15,
    ): FlatRoofBuilder {
        return new FlatRoofBuilder($width, $depth, $overhang, $drainageAngle, $panelThickness);
    }

    /** Pultdach — single slope */
    public static function shed(
        float $width,
        float $depth,
        float $roofHeight,
        float $overhang = 0.5,
        int $rafterCount = 4,
        float $panelThickness = 0.10,
    ): ShedRoofBuilder {
        return new ShedRoofBuilder($width, $depth, $roofHeight, $overhang, $rafterCount, $panelThickness);
    }

    /** Strohdach — asymmetric gable, front panel extends over porch */
    public static function thatched(
        float $width,
        float $depth,
        float $roofHeight,
        float $overhang = 0.7,
        float $frontExtension = 0.0,
        int $rafterCount = 4,
        float $panelThickness = 0.12,
    ): ThatchedRoofBuilder {
        return new ThatchedRoofBuilder($width, $depth, $roofHeight, $overhang, $frontExtension, $rafterCount, $panelThickness);
    }

    /** Mansarddach — steep lower slope, shallow upper slope */
    public static function mansard(
        float $width,
        float $depth,
        float $roofHeight,
        float $overhang = 0.4,
        float $breakRatio = 0.6,
        int $rafterCount = 3,
        float $panelThickness = 0.08,
    ): MansardRoofBuilder {
        return new MansardRoofBuilder($width, $depth, $roofHeight, $overhang, $breakRatio, $rafterCount, $panelThickness);
    }
}
