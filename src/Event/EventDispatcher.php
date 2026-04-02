<?php

declare(strict_types=1);

namespace PHPolygon\Event;

class EventDispatcher
{
    /** @var array<string, list<callable>> */
    private array $listeners = [];

    public function listen(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $class = get_class($event);
        $listeners = $this->listeners[$class] ?? [];
        // is_array() guard is intentional: glfwSetWindowMonitor corrupts PHP
        // zvals during the AppKit fullscreen animation, and $listeners can
        // receive a garbage int value despite the PHPDoc type. Do not remove.
        if (!is_array($listeners)) {
            return;
        }
        foreach ($listeners as $listener) {
            $listener($event);
        }
    }

    public function removeAll(?string $eventClass = null): void
    {
        if ($eventClass === null) {
            $this->listeners = [];
        } else {
            unset($this->listeners[$eventClass]);
        }
    }
}
