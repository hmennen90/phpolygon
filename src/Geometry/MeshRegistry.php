<?php

declare(strict_types=1);

namespace PHPolygon\Geometry;

class MeshRegistry
{
    /** @var array<string, MeshData> */
    private static array $registry = [];

    public static function register(string $id, MeshData $mesh): void
    {
        self::$registry[$id] = $mesh;
    }

    public static function get(string $id): ?MeshData
    {
        return self::$registry[$id] ?? null;
    }

    public static function has(string $id): bool
    {
        return isset(self::$registry[$id]);
    }

    public static function clear(): void
    {
        self::$registry = [];
    }

    /** @return string[] */
    public static function ids(): array
    {
        return array_keys(self::$registry);
    }
}
