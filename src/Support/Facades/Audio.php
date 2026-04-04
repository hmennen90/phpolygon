<?php

declare(strict_types=1);

namespace PHPolygon\Support\Facades;

/**
 * @method static void loadClip(string $id, string $path)
 * @method static void play(string $id, bool $loop = false)
 * @method static void stop(string $id)
 * @method static void setVolume(float $volume)
 * @method static void setPlaybackVolume(string $id, float $volume)
 * @method static void dispose()
 *
 * @see \PHPolygon\Audio\AudioManager
 */
class Audio extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'audio';
    }
}
