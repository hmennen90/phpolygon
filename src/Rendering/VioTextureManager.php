<?php

declare(strict_types=1);

namespace PHPolygon\Rendering;

use RuntimeException;
use VioContext;
use VioTexture;

class VioTextureManager extends TextureManager
{
    /** @var array<string, Texture> */
    private array $vioManagedTextures = [];

    /** @var array<string, VioTexture> */
    private array $vioTextureObjects = [];

    private string $vioBasePath;
    private int $nextId = 1;
    private ?VioRenderer2D $renderer = null;

    public function __construct(
        private readonly VioContext $ctx,
        string $basePath = '',
    ) {
        parent::__construct($basePath);
        $this->vioBasePath = rtrim($basePath, '/');
    }

    public function setRenderer(VioRenderer2D $renderer): void
    {
        $this->renderer = $renderer;
    }

    public function load(string $id, ?string $path = null): Texture
    {
        if (isset($this->vioManagedTextures[$id])) {
            return $this->vioManagedTextures[$id];
        }

        $filePath = $path ?? ($this->vioBasePath !== '' ? $this->vioBasePath . '/' . $id : $id);

        if (!file_exists($filePath)) {
            throw new RuntimeException("Texture file not found: {$filePath}");
        }

        $vioTex = vio_texture($this->ctx, ['file' => $filePath]);
        if ($vioTex === false) {
            throw new RuntimeException("Failed to load texture via vio: {$filePath}");
        }

        $textureId = $this->nextId++;
        $this->vioTextureObjects[$id] = $vioTex;

        $size = function_exists('vio_texture_size') ? vio_texture_size($vioTex) : [0, 0];
        $texture = new Texture($textureId, $size[0], $size[1], $filePath);
        $this->vioManagedTextures[$id] = $texture;

        if ($this->renderer !== null) {
            $this->renderer->registerVioTexture($textureId, $vioTex);
        }

        return $texture;
    }

    public function get(string $id): ?Texture
    {
        return $this->vioManagedTextures[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->vioManagedTextures[$id]);
    }

    public function unload(string $id): void
    {
        unset($this->vioTextureObjects[$id]);
        unset($this->vioManagedTextures[$id]);
    }

    public function clear(): void
    {
        $this->vioTextureObjects = [];
        $this->vioManagedTextures = [];
    }

    public function setBasePath(string $path): void
    {
        $this->vioBasePath = rtrim($path, '/');
        parent::setBasePath($path);
    }
}
