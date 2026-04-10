<?php

declare(strict_types=1);

namespace PHPolygon\Geometry;

class MeshCache
{
    private static ?string $cacheDir = null;

    public static function configure(string $cacheDir): void
    {
        self::$cacheDir = rtrim($cacheDir, '/\\');
    }

    /**
     * @param callable(): MeshData $generator
     */
    public static function resolve(string $id, callable $generator, string $version = '1'): MeshData
    {
        $mesh = self::loadFromCache($id, $version);

        if ($mesh === null) {
            /** @var MeshData $mesh */
            $mesh = $generator();
            self::writeToCache($id, $mesh, $version);
        }

        MeshRegistry::register($id, $mesh);

        return $mesh;
    }

    public static function clear(): void
    {
        if (self::$cacheDir === null || !is_dir(self::$cacheDir)) {
            return;
        }

        $files = glob(self::$cacheDir . '/*.mesh');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            unlink($file);
        }
    }

    public static function clearOne(string $id): void
    {
        $path = self::cachePath($id);
        if ($path !== null && file_exists($path)) {
            unlink($path);
        }
    }

    public static function isConfigured(): bool
    {
        return self::$cacheDir !== null;
    }

    /** @internal For testing only */
    public static function reset(): void
    {
        self::$cacheDir = null;
    }

    private static function loadFromCache(string $id, string $version): ?MeshData
    {
        $path = self::cachePath($id);
        if ($path === null || !file_exists($path)) {
            return null;
        }

        $data = file_get_contents($path);
        if ($data === false) {
            return null;
        }

        $storedHash = MeshCacheIO::readVersionHash($data);
        if ($storedHash === null || $storedHash !== MeshCacheIO::versionHash($version)) {
            return null;
        }

        return MeshCacheIO::decode($data);
    }

    private static function writeToCache(string $id, MeshData $mesh, string $version): void
    {
        $path = self::cachePath($id);
        if ($path === null) {
            return;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmpPath = $path . '.tmp';
        file_put_contents($tmpPath, MeshCacheIO::encode($mesh, $version));
        rename($tmpPath, $path);
    }

    private static function cachePath(string $id): ?string
    {
        if (self::$cacheDir === null) {
            return null;
        }

        $safeId = preg_replace('/[^a-zA-Z0-9_.\-]/', '_', $id);

        return self::$cacheDir . '/' . $safeId . '.mesh';
    }
}
