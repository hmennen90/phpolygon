<?php

declare(strict_types=1);

namespace PHPolygon\Prefab\Door;

/**
 * Static factory for creating door builders.
 *
 * Usage:
 *   $door = DoorBuilder::single(width: 0.8, height: 1.8, hingeSide: 'left');
 *   $door->withPrefix('Hut')->build($builder, $position, $rotation, $materials);
 */
class DoorBuilder
{
    /** Einzeltuer — single panel with hinge on one edge */
    public static function single(
        float $width = 0.8,
        float $height = 1.8,
        float $thickness = 0.04,
        string $hingeSide = 'left',
        float $maxAngle = 1.8,
        float $damping = 2.5,
        float $mass = 8.0,
        float $initialAngle = 0.0,
    ): SingleDoorBuilder {
        return new SingleDoorBuilder($width, $height, $thickness, $hingeSide, $maxAngle, $damping, $mass, $initialAngle);
    }

    /** Doppeltuer — two panels with opposing hinges */
    public static function double(
        float $width = 1.6,
        float $height = 2.0,
        float $thickness = 0.04,
        float $maxAngle = 1.6,
        float $damping = 2.5,
        float $mass = 6.0,
    ): DoubleDoorBuilder {
        return new DoubleDoorBuilder($width, $height, $thickness, $maxAngle, $damping, $mass);
    }

    /** Schiebetuer — slides along a rail */
    public static function sliding(
        float $width = 1.2,
        float $height = 2.0,
        float $thickness = 0.04,
        float $slideDistance = 0.0,
        string $slideDirection = 'right',
        float $damping = 4.0,
        float $mass = 10.0,
    ): SlidingDoorBuilder {
        return new SlidingDoorBuilder($width, $height, $thickness, $slideDistance, $slideDirection, $damping, $mass);
    }

    /** Falltuer/Klappe — horizontal panel that opens up/down */
    public static function trapdoor(
        float $width = 0.8,
        float $depth = 0.8,
        float $thickness = 0.04,
        float $openAngle = 1.5,
        string $hingeSide = 'back',
        float $damping = 3.0,
        float $mass = 10.0,
    ): TrapdoorBuilder {
        return new TrapdoorBuilder($width, $depth, $thickness, $openAngle, $hingeSide, $damping, $mass);
    }

    /** Drehtuer — rotates around center, no angle limits */
    public static function revolving(
        float $width = 0.8,
        float $height = 2.0,
        float $thickness = 0.04,
        int $segments = 4,
        float $damping = 1.5,
        float $mass = 15.0,
    ): RevolvingDoorBuilder {
        return new RevolvingDoorBuilder($width, $height, $thickness, $segments, $damping, $mass);
    }
}
