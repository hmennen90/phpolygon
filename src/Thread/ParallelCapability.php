<?php

declare(strict_types=1);

namespace PHPolygon\Thread;

/**
 * Runtime detection for PHP parallel extension and CPU capabilities.
 */
final class ParallelCapability
{
    /**
     * Check if the parallel extension is available (requires ZTS PHP).
     */
    public static function isAvailable(): bool
    {
        return \PHP_ZTS && extension_loaded('parallel');
    }

    /**
     * Detect the number of logical CPU cores.
     */
    public static function getCpuCount(): int
    {
        // Linux
        if (is_file('/proc/cpuinfo')) {
            $content = file_get_contents('/proc/cpuinfo');
            if ($content !== false) {
                $count = substr_count($content, 'processor');
                if ($count > 0) {
                    return $count;
                }
            }
        }

        // macOS / BSD
        if (\PHP_OS_FAMILY === 'Darwin') {
            $result = shell_exec('sysctl -n hw.ncpu');
            if ($result !== null && $result !== false) {
                $count = (int) trim($result);
                if ($count > 0) {
                    return $count;
                }
            }
        }

        // Windows
        $envCores = $_SERVER['NUMBER_OF_PROCESSORS'] ?? $_ENV['NUMBER_OF_PROCESSORS'] ?? null;
        if (is_string($envCores) || is_int($envCores)) {
            $count = (int) $envCores;
            if ($count > 0) {
                return $count;
            }
        }

        return 4; // safe fallback
    }

    /**
     * Recommended number of worker threads (reserves 1 core for OS/main thread).
     */
    public static function getRecommendedThreadCount(): int
    {
        return min(self::getCpuCount() - 1, 8);
    }

    /**
     * Determine the threading mode based on config and runtime capabilities.
     */
    public static function resolveMode(?ThreadingMode $requested): ThreadingMode
    {
        if ($requested !== null) {
            return $requested;
        }

        return self::isAvailable() ? ThreadingMode::MultiThreaded : ThreadingMode::SingleThreaded;
    }

    /**
     * Path to the active Composer autoloader, used to bootstrap worker Runtimes
     * so they can autoload engine/game classes. A parallel Runtime starts a fresh
     * thread with NO autoloader, so without this a worker cannot resolve any
     * class by name (fatal). Resolved from the loaded {@see \Composer\Autoload\ClassLoader}
     * (vendor/composer/ClassLoader.php → vendor/autoload.php), so it points at the
     * real vendor dir whether the engine runs standalone or as a dependency.
     *
     * Null when no Composer autoloader is present (e.g. a bundled PHAR with a
     * custom loader) — callers then spawn an unbootstrapped Runtime, which is
     * only safe for closures that touch no project classes.
     *
     * Resolving this costs a reflection lookup, so the result is memoised: it
     * cannot change within a process.
     */
    public static function autoloadBootstrap(): ?string
    {
        if (self::$bootstrapResolved) {
            return self::$bootstrap;
        }
        self::$bootstrapResolved = true;

        if (!class_exists(\Composer\Autoload\ClassLoader::class, false)) {
            return self::$bootstrap = null;
        }

        $file = (new \ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName();
        if ($file === false) {
            return self::$bootstrap = null;
        }

        $autoload = dirname($file, 2) . '/autoload.php';

        return self::$bootstrap = is_file($autoload) ? $autoload : null;
    }

    private static ?string $bootstrap = null;

    private static bool $bootstrapResolved = false;
}
