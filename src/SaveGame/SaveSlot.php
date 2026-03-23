<?php

declare(strict_types=1);

namespace PHPolygon\SaveGame;

class SaveSlot
{
    public function __construct(
        public readonly int $index,
        public readonly string $name,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
        public readonly float $playTime,
        /** @var array<string, mixed> */
        public readonly array $metadata,
        /** @var array<string, mixed> */
        public readonly array $data,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'name' => $this->name,
            'createdAt' => $this->createdAt->format('c'),
            'updatedAt' => $this->updatedAt->format('c'),
            'playTime' => $this->playTime,
            'metadata' => $this->metadata,
            'data' => $this->data,
        ];
    }

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            index: $raw['index'],
            name: $raw['name'],
            createdAt: new \DateTimeImmutable($raw['createdAt']),
            updatedAt: new \DateTimeImmutable($raw['updatedAt']),
            playTime: (float) $raw['playTime'],
            metadata: $raw['metadata'] ?? [],
            data: $raw['data'] ?? [],
        );
    }
}
