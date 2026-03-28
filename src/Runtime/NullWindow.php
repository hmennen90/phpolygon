<?php

declare(strict_types=1);

namespace PHPolygon\Runtime;

/**
 * A window implementation that requires no GPU or display server.
 * Used for headless mode: testing, validation, CI, editor dry-runs.
 */
class NullWindow extends Window
{
    private bool $shouldCloseFlag = false;
    private int $nullWidth;
    private int $nullHeight;

    public function __construct(
        int $width = 1280,
        int $height = 720,
        string $title = 'PHPolygon (headless)',
    ) {
        parent::__construct($width, $height, $title, false, false);
        $this->nullWidth = $width;
        $this->nullHeight = $height;
    }

    public function initialize(InputInterface $input): void
    {
        // No GLFW, no GL context — just mark as ready
    }

    public function shouldClose(): bool
    {
        return $this->shouldCloseFlag;
    }

    public function requestClose(): void
    {
        $this->shouldCloseFlag = true;
    }

    public function pollEvents(): void {}
    public function swapBuffers(): void {}

    public function getWidth(): int { return $this->nullWidth; }
    public function getHeight(): int { return $this->nullHeight; }
    public function getFramebufferWidth(): int { return $this->nullWidth; }
    public function getFramebufferHeight(): int { return $this->nullHeight; }
    public function getContentScaleX(): float { return 1.0; }
    public function getContentScaleY(): float { return 1.0; }
    public function getPixelRatio(): float { return 1.0; }
    public function getHandle(): object { throw new \RuntimeException('No window handle in headless mode'); }
    public function setTitle(string $title): void {}
    public function setFullscreen(): void {}
    public function setWindowed(): void {}
    public function setSize(int $width, int $height): void {}
    public function toggleFullscreen(): void {}
    public function isFullscreen(): bool { return false; }
    public function destroy(): void {}
}
