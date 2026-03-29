<?php

declare(strict_types=1);

namespace PHPolygon\Component;

use PHPolygon\ECS\AbstractComponent;
use PHPolygon\ECS\Attribute\Category;
use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Serializable;
use PHPolygon\Math\Vec3;

/**
 * Axis-aligned 3D box collider.
 * Size is the full extent (not half-extent). Centered on the entity's position + offset.
 */
#[Serializable]
#[Category('Physics')]
class BoxCollider3D extends AbstractComponent
{
    #[Property(editorHint: 'vec3')]
    public Vec3 $size;

    #[Property(editorHint: 'vec3')]
    public Vec3 $offset;

    #[Property]
    public bool $isTrigger;

    #[Property]
    public bool $isStatic;

    public function __construct(
        ?Vec3 $size = null,
        ?Vec3 $offset = null,
        bool $isTrigger = false,
        bool $isStatic = true,
    ) {
        $this->size = $size ?? new Vec3(1.0, 1.0, 1.0);
        $this->offset = $offset ?? Vec3::zero();
        $this->isTrigger = $isTrigger;
        $this->isStatic = $isStatic;
    }

    /**
     * Get the world-space AABB min/max, accounting for entity rotation and scale.
     * Transforms all 8 corners through the world matrix to compute a tight AABB.
     *
     * @return array{min: Vec3, max: Vec3}
     */
    public function getWorldAABB(\PHPolygon\Math\Mat4 $worldMatrix): array
    {
        $hx = $this->size->x * 0.5;
        $hy = $this->size->y * 0.5;
        $hz = $this->size->z * 0.5;
        $off = $this->offset;

        $minX = PHP_FLOAT_MAX; $minY = PHP_FLOAT_MAX; $minZ = PHP_FLOAT_MAX;
        $maxX = -PHP_FLOAT_MAX; $maxY = -PHP_FLOAT_MAX; $maxZ = -PHP_FLOAT_MAX;

        foreach ([[-1, 1], [1, 1], [-1, -1], [1, -1]] as [$sx, $sz]) {
            foreach ([-1, 1] as $sy) {
                $corner = $worldMatrix->transformPoint(new Vec3(
                    $off->x + $sx * $hx,
                    $off->y + $sy * $hy,
                    $off->z + $sz * $hz,
                ));
                $minX = min($minX, $corner->x); $maxX = max($maxX, $corner->x);
                $minY = min($minY, $corner->y); $maxY = max($maxY, $corner->y);
                $minZ = min($minZ, $corner->z); $maxZ = max($maxZ, $corner->z);
            }
        }

        return [
            'min' => new Vec3($minX, $minY, $minZ),
            'max' => new Vec3($maxX, $maxY, $maxZ),
        ];
    }
}
