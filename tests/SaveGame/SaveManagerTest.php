<?php

declare(strict_types=1);

namespace PHPolygon\Tests\SaveGame;

use PHPUnit\Framework\TestCase;
use PHPolygon\SaveGame\SaveManager;
use PHPolygon\SaveGame\SaveSlot;
use PHPolygon\SaveGame\SaveSlotInfo;

class SaveManagerTest extends TestCase
{
    private SaveManager $manager;
    private string $savePath;

    protected function setUp(): void
    {
        $this->savePath = sys_get_temp_dir() . '/phpolygon_save_test_' . uniqid();
        $this->manager = new SaveManager($this->savePath, 3);
    }

    protected function tearDown(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $path = $this->savePath . '/slot_' . $i . '.save.json';
            if (file_exists($path)) {
                unlink($path);
            }
        }
        if (is_dir($this->savePath)) {
            rmdir($this->savePath);
        }
    }

    public function testSaveAndLoad(): void
    {
        $data = ['level' => 5, 'hp' => 100];
        $meta = ['levelName' => 'Forest'];

        $slot = $this->manager->save(0, 'Save 1', $data, $meta, 3600.0);

        $this->assertInstanceOf(SaveSlot::class, $slot);
        $this->assertEquals(0, $slot->index);
        $this->assertEquals('Save 1', $slot->name);
        $this->assertEquals($data, $slot->data);
        $this->assertEquals($meta, $slot->metadata);
        $this->assertEquals(3600.0, $slot->playTime);

        // Load from a fresh manager (forces disk read)
        $fresh = new SaveManager($this->savePath, 3);
        $loaded = $fresh->load(0);

        $this->assertNotNull($loaded);
        $this->assertEquals('Save 1', $loaded->name);
        $this->assertEquals($data, $loaded->data);
        $this->assertEquals($meta, $loaded->metadata);
        $this->assertEqualsWithDelta(3600.0, $loaded->playTime, 0.001);
    }

    public function testLoadReturnsNullForEmpty(): void
    {
        $this->assertNull($this->manager->load(0));
    }

    public function testSaveInvalidSlotThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->save(5, 'Bad', []);
    }

    public function testSaveNegativeSlotThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->save(-1, 'Bad', []);
    }

    public function testExists(): void
    {
        $this->assertFalse($this->manager->exists(0));

        $this->manager->save(0, 'Test', ['x' => 1]);
        $this->assertTrue($this->manager->exists(0));
    }

    public function testDelete(): void
    {
        $this->manager->save(0, 'Test', ['x' => 1]);
        $this->assertTrue($this->manager->exists(0));

        $this->manager->delete(0);
        $this->assertFalse($this->manager->exists(0));

        // Fresh manager also can't find it
        $fresh = new SaveManager($this->savePath, 3);
        $this->assertNull($fresh->load(0));
    }

    public function testDeleteNonexistentSlot(): void
    {
        // Should not throw
        $this->manager->delete(0);
        $this->assertFalse($this->manager->exists(0));
    }

    public function testOverwriteSlot(): void
    {
        $this->manager->save(0, 'First', ['v' => 1]);
        $slot1 = $this->manager->load(0);

        $this->manager->save(0, 'Second', ['v' => 2], [], 120.0);
        $slot2 = $this->manager->load(0);

        $this->assertEquals('Second', $slot2->name);
        $this->assertEquals(['v' => 2], $slot2->data);
        // createdAt should be preserved from original save
        $this->assertEquals($slot1->createdAt->format('c'), $slot2->createdAt->format('c'));
        // updatedAt should be newer
        $this->assertGreaterThanOrEqual($slot1->updatedAt, $slot2->updatedAt);
    }

    public function testListSlots(): void
    {
        $this->manager->save(0, 'Slot A', ['a' => 1], ['level' => 'Forest']);
        $this->manager->save(2, 'Slot C', ['c' => 3], ['level' => 'Desert']);

        $list = $this->manager->listSlots();

        $this->assertCount(2, $list);
        $this->assertContainsOnlyInstancesOf(SaveSlotInfo::class, $list);

        $this->assertEquals(0, $list[0]->index);
        $this->assertEquals('Slot A', $list[0]->name);
        $this->assertEquals(['level' => 'Forest'], $list[0]->metadata);

        $this->assertEquals(2, $list[1]->index);
        $this->assertEquals('Slot C', $list[1]->name);
    }

    public function testFindEmptySlot(): void
    {
        $this->assertEquals(0, $this->manager->findEmptySlot());

        $this->manager->save(0, 'A', []);
        $this->assertEquals(1, $this->manager->findEmptySlot());

        $this->manager->save(1, 'B', []);
        $this->assertEquals(2, $this->manager->findEmptySlot());

        $this->manager->save(2, 'C', []);
        $this->assertNull($this->manager->findEmptySlot());
    }

    public function testGetSavePath(): void
    {
        $this->assertEquals($this->savePath, $this->manager->getSavePath());
    }

    public function testGetMaxSlots(): void
    {
        $this->assertEquals(3, $this->manager->getMaxSlots());
    }
}
