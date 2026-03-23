<?php

declare(strict_types=1);

namespace PHPolygon\Tests\SaveGame;

use PHPUnit\Framework\TestCase;
use PHPolygon\SaveGame\SaveSlotInfo;

class SaveSlotInfoTest extends TestCase
{
    public function testFormattedPlayTime(): void
    {
        $info = new SaveSlotInfo(
            index: 0,
            name: 'Test',
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            playTime: 3661.0, // 1h 1m 1s
            metadata: [],
        );

        $this->assertEquals('1:01:01', $info->getFormattedPlayTime());
    }

    public function testFormattedPlayTimeZero(): void
    {
        $info = new SaveSlotInfo(
            index: 0,
            name: 'Test',
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            playTime: 0.0,
            metadata: [],
        );

        $this->assertEquals('0:00:00', $info->getFormattedPlayTime());
    }

    public function testFormattedPlayTimeLarge(): void
    {
        $info = new SaveSlotInfo(
            index: 0,
            name: 'Test',
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            playTime: 360000.0, // 100 hours
            metadata: [],
        );

        $this->assertEquals('100:00:00', $info->getFormattedPlayTime());
    }
}
