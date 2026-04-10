<?php

declare(strict_types=1);

namespace PHPolygon\Audio;

use VioSound;

class VioAudioBackend implements AudioBackendInterface
{
    /** @var array<string, string> clipId => file path */
    private array $clipPaths = [];

    /** @var array<int, VioSound> playbackId => VioSound */
    private array $activeSounds = [];

    private int $nextPlaybackId = 1;
    private float $masterVolume = 1.0;

    public function load(string $id, string $path): AudioClip
    {
        $this->clipPaths[$id] = $path;
        return new AudioClip($id, $path);
    }

    public function play(string $clipId, float $volume = 1.0, bool $loop = false): int
    {
        $path = $this->clipPaths[$clipId] ?? null;
        if ($path === null || !file_exists($path)) {
            return 0;
        }

        $sound = vio_audio_load($path);
        if ($sound === false) {
            return 0;
        }

        vio_audio_play($sound, [
            'volume' => $volume * $this->masterVolume,
            'loop' => $loop,
        ]);

        $playbackId = $this->nextPlaybackId++;
        $this->activeSounds[$playbackId] = $sound;

        return $playbackId;
    }

    public function stop(int $playbackId): void
    {
        if (isset($this->activeSounds[$playbackId])) {
            vio_audio_stop($this->activeSounds[$playbackId]);
            unset($this->activeSounds[$playbackId]);
        }
    }

    public function stopAll(): void
    {
        foreach ($this->activeSounds as $sound) {
            vio_audio_stop($sound);
        }
        $this->activeSounds = [];
    }

    public function setVolume(int $playbackId, float $volume): void
    {
        if (isset($this->activeSounds[$playbackId])) {
            vio_audio_volume($this->activeSounds[$playbackId], $volume * $this->masterVolume);
        }
    }

    public function isPlaying(int $playbackId): bool
    {
        if (!isset($this->activeSounds[$playbackId])) {
            return false;
        }
        return vio_audio_playing($this->activeSounds[$playbackId]);
    }

    public function setMasterVolume(float $volume): void
    {
        $this->masterVolume = max(0.0, min(1.0, $volume));
    }

    public function getMasterVolume(): float
    {
        return $this->masterVolume;
    }

    public function dispose(): void
    {
        $this->stopAll();
    }
}
