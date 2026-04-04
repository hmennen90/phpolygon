<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Runtime;

use PHPolygon\Runtime\NullWindow;
use PHPUnit\Framework\TestCase;

class WindowTest extends TestCase
{
    private NullWindow $window;

    protected function setUp(): void
    {
        $this->window = new NullWindow(1280, 720, 'Test');
    }

    // -------------------------------------------------------------------------
    // Initial state
    // -------------------------------------------------------------------------

    public function testInitialStateIsWindowed(): void
    {
        $this->assertFalse($this->window->isFullscreen());
        $this->assertFalse($this->window->isBorderless());
        $this->assertSame(1280, $this->window->getWidth());
        $this->assertSame(720,  $this->window->getHeight());
    }

    // -------------------------------------------------------------------------
    // Windowed → Fullscreen → Windowed
    // -------------------------------------------------------------------------

    public function testSetFullscreenTransition(): void
    {
        $this->window->setFullscreen();

        $this->assertTrue($this->window->isFullscreen());
        $this->assertFalse($this->window->isBorderless());
    }

    public function testSetFullscreenIsIdempotent(): void
    {
        $this->window->setFullscreen();
        $this->window->setFullscreen(); // second call must be a no-op

        $this->assertTrue($this->window->isFullscreen());
    }

    public function testRestoreToWindowedAfterFullscreen(): void
    {
        $this->window->setFullscreen();
        $this->window->setWindowed();

        $this->assertFalse($this->window->isFullscreen());
        $this->assertFalse($this->window->isBorderless());
        $this->assertSame(1280, $this->window->getWidth());
        $this->assertSame(720,  $this->window->getHeight());
    }

    // -------------------------------------------------------------------------
    // Windowed → Borderless → Windowed
    // -------------------------------------------------------------------------

    public function testSetBorderlessTransition(): void
    {
        $this->window->setBorderless();

        $this->assertFalse($this->window->isFullscreen());
        $this->assertTrue($this->window->isBorderless());
    }

    public function testSetBorderlessIsIdempotent(): void
    {
        $this->window->setBorderless();
        $this->window->setBorderless();

        $this->assertTrue($this->window->isBorderless());
    }

    public function testRestoreToWindowedAfterBorderless(): void
    {
        $this->window->setBorderless();
        $this->window->setWindowed();

        $this->assertFalse($this->window->isFullscreen());
        $this->assertFalse($this->window->isBorderless());
        $this->assertSame(1280, $this->window->getWidth());
        $this->assertSame(720,  $this->window->getHeight());
    }

    // -------------------------------------------------------------------------
    // Cross-mode transitions
    // -------------------------------------------------------------------------

    public function testFullscreenToBorderless(): void
    {
        $this->window->setFullscreen();
        $this->window->setBorderless();

        $this->assertFalse($this->window->isFullscreen());
        $this->assertTrue($this->window->isBorderless());
    }

    public function testBorderlessToFullscreen(): void
    {
        $this->window->setBorderless();
        $this->window->setFullscreen();

        $this->assertTrue($this->window->isFullscreen());
        $this->assertFalse($this->window->isBorderless());
    }

    public function testSetWindowedWhileAlreadyWindowedIsNoOp(): void
    {
        $this->window->setWindowed(); // already windowed — must not change anything

        $this->assertFalse($this->window->isFullscreen());
        $this->assertFalse($this->window->isBorderless());
        $this->assertSame(1280, $this->window->getWidth());
        $this->assertSame(720,  $this->window->getHeight());
    }

    // -------------------------------------------------------------------------
    // toggleFullscreen
    // -------------------------------------------------------------------------

    public function testToggleFullscreenEntersFullscreen(): void
    {
        $this->window->toggleFullscreen();

        $this->assertTrue($this->window->isFullscreen());
    }

    public function testToggleFullscreenExitsFullscreen(): void
    {
        $this->window->setFullscreen();
        $this->window->toggleFullscreen();

        $this->assertFalse($this->window->isFullscreen());
    }

    public function testToggleFullscreenRoundTrip(): void
    {
        $this->window->toggleFullscreen(); // → fullscreen
        $this->window->toggleFullscreen(); // → windowed

        $this->assertFalse($this->window->isFullscreen());
        $this->assertSame(1280, $this->window->getWidth());
        $this->assertSame(720,  $this->window->getHeight());
    }

    // -------------------------------------------------------------------------
    // setSize preserves windowed size for restore
    // -------------------------------------------------------------------------

    public function testSetSizeUpdatesWindowedDimensions(): void
    {
        $this->window->setSize(1920, 1080);

        $this->assertSame(1920, $this->window->getWidth());
        $this->assertSame(1080, $this->window->getHeight());
    }

    public function testSetSizeInWindowedIsRestoredAfterFullscreen(): void
    {
        $this->window->setSize(1600, 900);
        $this->window->setFullscreen();
        $this->window->setWindowed();

        $this->assertSame(1600, $this->window->getWidth());
        $this->assertSame(900,  $this->window->getHeight());
    }

    // -------------------------------------------------------------------------
    // Borderless → Fullscreen restores original windowed size
    // -------------------------------------------------------------------------

    public function testBorderlessToFullscreenToWindowedRestoresOriginalSize(): void
    {
        // Enter borderless (saves 1280×720 as windowed size)
        $this->window->setBorderless();
        // Switch to fullscreen (must keep the saved 1280×720 windowed geometry)
        $this->window->setFullscreen();
        // Restore
        $this->window->setWindowed();

        $this->assertSame(1280, $this->window->getWidth());
        $this->assertSame(720,  $this->window->getHeight());
    }
}
