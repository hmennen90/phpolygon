<?php

declare(strict_types=1);

namespace PHPolygon\Geometry;

class MeshRegistry
{
    /** @var array<string, MeshData> */
    private static array $registry = [];

    /** @var array<string, int> Increments on every register() so renderers can invalidate GPU caches for dynamic meshes (skinning, morph targets). */
    private static array $versions = [];

    public static function register(string $id, MeshData $mesh): void
    {
        self::$registry[$id] = $mesh;
        self::$versions[$id] = (self::$versions[$id] ?? 0) + 1;
    }

    public static function get(string $id): ?MeshData
    {
        return self::$registry[$id] ?? null;
    }

    public static function has(string $id): bool
    {
        return isset(self::$registry[$id]);
    }

    /**
     * Monotonic version counter, incremented every time a mesh is (re-)registered
     * under this id. Renderers compare against the last uploaded version to
     * decide whether to re-upload the GPU buffer for dynamic meshes.
     */
    public static function version(string $id): int
    {
        return self::$versions[$id] ?? 0;
    }

    public static function clear(): void
    {
        self::$registry = [];
        self::$versions = [];
    }

    /** @return string[] */
    public static function ids(): array
    {
        return array_keys(self::$registry);
    }
}
