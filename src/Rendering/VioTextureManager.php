<?php

declare(strict_types=1);

namespace PHPolygon\Rendering;

use PHPolygon\Runtime\PerfProfiler;
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

    /** Anisotropic filtering level (1..16) applied to textures loaded from now on; follows GraphicsSettings. */
    private int $anisotropy = 4;

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

        PerfProfiler::begin('texture.upload');
        try {
            // Mip chain + anisotropic filtering: without mips a texture seen at
            // a distance (sign plates, nameplates, decals) aliases into a
            // shimmer as the sampler skips across texels; the driver's mip
            // selection plus anisotropy keeps oblique views crisp instead of
            // smeared. Both keys are honoured from php-vio 2.9 on (mip chains
            // on D3D12 from 2.10) and are silently ignored by older builds,
            // which then behave exactly as before.
            $vioTex = vio_texture($this->ctx, [
                'file' => $filePath,
                'mipmaps' => true,
                'anisotropy' => $this->anisotropy,
            ]);
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
        } finally {
            PerfProfiler::end();
        }
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

    /**
     * Apply graphics settings to the vio sampler state.
     *
     * Vio handles the sampler internally; the only knob exposed at PHP level
     * is the optional vio_set_default_anisotropy() helper (where it exists).
     * The base TextureManager already stores the values for any future
     * texture-resize work, so we delegate first and then attempt the vio call.
     */
    public function applySettings(GraphicsSettings $settings): void
    {
        parent::applySettings($settings);

        // Textures loaded from here on pick up the new level; already-uploaded
        // ones keep theirs (vio samplers are baked at creation).
        $this->anisotropy = max(1, min(16, $settings->anisotropy));

        if (function_exists('vio_set_default_anisotropy')) {
            try {
                @vio_set_default_anisotropy($this->ctx, $settings->anisotropy);
            } catch (\Throwable $e) {
                // Older vio builds may not expose this helper - ignore.
            }
        }
    }
}
