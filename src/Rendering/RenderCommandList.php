<?php

declare(strict_types=1);

namespace PHPolygon\Rendering;

class RenderCommandList
{
    /** @var list<object> */
    private array $commands = [];

    public function add(object $command): void
    {
        $this->commands[] = $command;
    }

    public function clear(): void
    {
        $this->commands = [];
    }

    /** @return list<object> */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return list<T>
     */
    public function ofType(string $type): array
    {
        $result = [];
        foreach ($this->commands as $command) {
            if ($command instanceof $type) {
                $result[] = $command;
            }
        }
        return $result;
    }

    public function count(): int
    {
        return count($this->commands);
    }

    public function isEmpty(): bool
    {
        return $this->commands === [];
    }
}
