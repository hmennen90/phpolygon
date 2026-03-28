<?php

declare(strict_types=1);

namespace PHPolygon\Rendering;

/**
 * A 3D renderer that accepts all commands but produces no output.
 * Used for headless mode: testing, validation, CI pipelines.
 * Stores the last command list for test assertions.
 */
class NullRenderer3D implements Renderer3DInterface
{
    private int $width;
    private int $height;
    private ?RenderCommandList $lastCommandList = null;

    public function __construct(int $width = 1280, int $height = 720)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function beginFrame(): void {}
    public function endFrame(): void {}
    public function clear(Color $color): void {}

    public function setViewport(int $x, int $y, int $width, int $height): void
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getWidth(): int { return $this->width; }
    public function getHeight(): int { return $this->height; }

    public function render(RenderCommandList $commandList): void
    {
        // Store a snapshot so test assertions survive the post-render clear()
        $snapshot = new RenderCommandList();
        foreach ($commandList->getCommands() as $cmd) {
            $snapshot->add($cmd);
        }
        $this->lastCommandList = $snapshot;
    }

    public function getLastCommandList(): ?RenderCommandList
    {
        return $this->lastCommandList;
    }
}
