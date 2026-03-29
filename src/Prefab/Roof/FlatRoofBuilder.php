<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Roof;

use PHPolygon\Component\MeshRenderer;
use PHPolygon\Component\Transform3D;
use PHPolygon\Math\Quaternion;
use PHPolygon\Math\Vec3;
use PHPolygon\Scene\SceneBuilder;

/**
 * Flachdach — single horizontal panel with slight drainage slope.
 */
class FlatRoofBuilder extends AbstractRoofBuilder
{
    private float $drainageAngle;

    public function __construct(
        float $width,
        float $depth,
        float $overhang = 0.3,
        float $drainageAngle = 2.0,
        float $panelThickness = 0.15,
    ) {
        $this->width = $width;
        $this->depth = $depth;
        $this->roofHeight = 0.0;
        $this->overhang = $overhang;
        $this->drainageAngle = $drainageAngle;
        $this->rafterCount = 0;
        $this->panelThickness = $panelThickness;
    }

    public function build(
        SceneBuilder $builder,
        Vec3 $basePosition,
        Quaternion $baseRotation,
        RoofMaterials $materials,
    ): RoofResult {
        $panelW = $this->width * 0.5 + $this->overhang;
        $panelD = $this->depth * 0.5 + $this->overhang;
        $tiltRad = deg2rad($this->drainageAngle);

        $yawQ = $this->extractYawQuaternion($baseRotation);
        $tilt = Quaternion::fromAxisAngle(new Vec3(1.0, 0.0, 0.0), $tiltRad);
        $panelRot = $yawQ->multiply($tilt);

        $builder->entity($this->prefix . '_RoofFlat')
            ->with(new Transform3D(
                position: $basePosition,
                rotation: $panelRot,
                scale: new Vec3($panelW, $this->panelThickness, $panelD),
            ))
            ->with(new MeshRenderer(meshId: 'box', materialId: $materials->panel));

        return new RoofResult(
            ridgeY: $basePosition->y + $this->panelThickness,
            eaveY: $basePosition->y,
            entityCount: 1,
        );
    }
}
