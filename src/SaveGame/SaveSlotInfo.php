<?php

declare(strict_types=1);

namespace PHPolygon\SaveGame;

/**
 * Lightweight summary of a save slot (no game data loaded).
 */
class SaveSlotInfo
{
    public function __construct(
        public readonly int $index,
        public readonly string $name,
        public readonly ?\DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $updatedAt,
        public readonly float $playTime,
        /** @var array<string, mixed> */
        public readonly array $metadata,
    ) {}

    /**
     * Format play time as H:MM:SS.
     */
    public function getFormattedPlayTime(): string
    {
        $total = (int) $this->playTime;
        $hours = intdiv($total, 3600);
        $minutes = intdiv($total % 3600, 60);
        $seconds = $total % 60;

        return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
