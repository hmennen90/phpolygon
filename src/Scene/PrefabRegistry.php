<?php

declare(strict_types=1);

namespace PHPolygon\Scene;

use RuntimeException;

class PrefabRegistry
{
    /** @var array<string, class-string<PrefabInterface>> */
    private array $prefabs = [];

    /**
     * @param class-string<PrefabInterface> $prefabClass
     */
    public function register(string $prefabClass): void
    {
        $name = $prefabClass::getName();
        $this->prefabs[$name] = $prefabClass;
    }

    public function create(string $name): PrefabInterface
    {
        if (!isset($this->prefabs[$name])) {
            throw new RuntimeException("Prefab '{$name}' is not registered");
        }

        return new $this->prefabs[$name]();
    }

    public function has(string $name): bool
    {
        return isset($this->prefabs[$name]);
    }

    /**
     * @return array<string, class-string<PrefabInterface>>
     */
    public function all(): array
    {
        return $this->prefabs;
    }
}
