<?php

declare(strict_types=1);

namespace PHPolygon\Rendering;

/**
 * Registry for cubemap data. Supports both file-based (CubemapFaces) and
 * procedurally generated (CubemapData) cubemaps.
 * Face order: +X, -X, +Y, -Y, +Z, -Z (standard OpenGL cubemap order).
 */
class CubemapRegistry
{
    /** @var array<string, CubemapFaces> */
    private static array $registry = [];

    /** @var array<string, CubemapData> */
    private static array $proceduralRegistry = [];

    public static function register(string $id, CubemapFaces $faces): void
    {
        self::$registry[$id] = $faces;
        unset(self::$proceduralRegistry[$id]);
    }

    public static function registerProcedural(string $id, CubemapData $data): void
    {
        self::$proceduralRegistry[$id] = $data;
        unset(self::$registry[$id]);
    }

    public static function get(string $id): ?CubemapFaces
    {
        return self::$registry[$id] ?? null;
    }

    public static function getProcedural(string $id): ?CubemapData
    {
        return self::$proceduralRegistry[$id] ?? null;
    }

    public static function has(string $id): bool
    {
        return isset(self::$registry[$id]) || isset(self::$proceduralRegistry[$id]);
    }

    public static function isProcedural(string $id): bool
    {
        return isset(self::$proceduralRegistry[$id]);
    }

    public static function clear(): void
    {
        self::$registry = [];
        self::$proceduralRegistry = [];
    }
}
