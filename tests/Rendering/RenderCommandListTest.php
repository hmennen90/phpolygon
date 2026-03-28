<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Rendering;

use PHPUnit\Framework\TestCase;
use PHPolygon\Math\Mat4;
use PHPolygon\Rendering\Command\DrawMesh;
use PHPolygon\Rendering\Command\SetCamera;
use PHPolygon\Rendering\RenderCommandList;

class RenderCommandListTest extends TestCase
{
    public function testEmptyByDefault(): void
    {
        $list = new RenderCommandList();
        $this->assertTrue($list->isEmpty());
        $this->assertEquals(0, $list->count());
    }

    public function testAddIncreasesCount(): void
    {
        $list = new RenderCommandList();
        $list->add(new DrawMesh('box', 'stone', Mat4::identity()));
        $this->assertEquals(1, $list->count());
        $this->assertFalse($list->isEmpty());
    }

    public function testGetCommandsReturnsInInsertionOrder(): void
    {
        $list = new RenderCommandList();
        $a = new DrawMesh('a', 'mat', Mat4::identity());
        $b = new DrawMesh('b', 'mat', Mat4::identity());
        $list->add($a);
        $list->add($b);

        $commands = $list->getCommands();
        $this->assertCount(2, $commands);
        $this->assertSame($a, $commands[0]);
        $this->assertSame($b, $commands[1]);
    }

    public function testOfTypeFiltersCorrectly(): void
    {
        $list = new RenderCommandList();
        $draw = new DrawMesh('box', 'stone', Mat4::identity());
        $cam  = new SetCamera(Mat4::identity(), Mat4::identity());
        $list->add($draw);
        $list->add($cam);
        $list->add(new DrawMesh('sphere', 'glass', Mat4::identity()));

        $draws = $list->ofType(DrawMesh::class);
        $this->assertCount(2, $draws);
        $this->assertContainsOnlyInstancesOf(DrawMesh::class, $draws);

        $cams = $list->ofType(SetCamera::class);
        $this->assertCount(1, $cams);
    }

    public function testClearEmptiesTheList(): void
    {
        $list = new RenderCommandList();
        $list->add(new DrawMesh('box', 'stone', Mat4::identity()));
        $list->add(new DrawMesh('sphere', 'glass', Mat4::identity()));
        $list->clear();
        $this->assertTrue($list->isEmpty());
        $this->assertEquals(0, $list->count());
    }
}
