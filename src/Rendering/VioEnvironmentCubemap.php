<?php

declare(strict_types=1);

namespace PHPolygon\Rendering;

use PHPolygon\Math\Mat4;
use PHPolygon\Math\Vec3;
use PHPolygon\Rendering\Command\SetSky;

/**
 * GPU-rendered environment (reflection) cubemap on the vio backend.
 *
 * Owns a cube render target with a full mip chain. The renderer draws the
 * atmospheric sky into its six faces whenever the SetSky parameters change
 * (sky hash), then builds the mips so the mesh shader can pick a roughness-
 * appropriate LOD via textureLod(). Replaces the ext-metal `MetalCubemapTarget`
 * — same face size / mip layout, but portable across every vio backend that
 * reports VIO_FEATURE_RENDER_TARGET_CUBE (Metal, OpenGL today).
 */
final class VioEnvironmentCubemap
{
    /** Resolution per face — 128² keeps the six passes well below the frame budget. */
    public const FACE_SIZE = 128;

    private ?\VioRenderTarget $target = null;
    private ?\VioCubemap $cubemap = null;
    private string $lastSkyHash = '';
    private bool $unsupported = false;

    public function __construct(private readonly \VioContext $ctx)
    {
    }

    public static function isSupported(\VioContext $ctx): bool
    {
        return \function_exists('vio_render_target_cubemap')
            && vio_supports_feature($ctx, \VIO_FEATURE_RENDER_TARGET_CUBE)
            && vio_supports_feature($ctx, \VIO_FEATURE_MIPMAP_GEN);
    }

    /** Lazily allocate the cube target. False when the backend cannot. */
    public function ensureAllocated(): bool
    {
        if ($this->target !== null) {
            return true;
        }
        if ($this->unsupported || !self::isSupported($this->ctx)) {
            $this->unsupported = true;
            return false;
        }
        $rt = vio_render_target($this->ctx, ['cube' => true, 'size' => self::FACE_SIZE, 'mipmaps' => true]);
        if ($rt === false) {
            $this->unsupported = true;
            return false;
        }
        $this->target = $rt;
        return true;
    }

    public function target(): ?\VioRenderTarget
    {
        return $this->target;
    }

    /** The cube as a bindable cubemap (valid after the first update). */
    public function cubemap(): ?\VioCubemap
    {
        if ($this->cubemap === null && $this->target !== null && $this->lastSkyHash !== '') {
            $cm = vio_render_target_cubemap($this->target);
            $this->cubemap = $cm === false ? null : $cm;
        }
        return $this->cubemap;
    }

    public function needsUpdate(SetSky $sky): bool
    {
        return self::skyHash($sky) !== $this->lastSkyHash;
    }

    public function markRendered(SetSky $sky): void
    {
        $this->lastSkyHash = self::skyHash($sky);
    }

    /** floor(log2(FACE_SIZE)) — the last mip level, for roughness → LOD mapping. */
    public static function mipMax(): float
    {
        return (float) (int) floor(log(self::FACE_SIZE, 2));
    }

    /**
     * inverse(projection * faceView) per face, GL clip convention (the sky
     * shaders unproject NDC z = 1 into a world direction — the depth range is
     * irrelevant for the direction). Face order +X,-X,+Y,-Y,+Z,-Z with the
     * standard cubemap up-vectors so `textureLod(cube, R)` maps world space.
     *
     * @return array<int, float[]>
     */
    public static function faceInverseViewProjections(): array
    {
        $proj = Mat4::perspective(M_PI / 2.0, 1.0, 0.1, 10.0);
        $eye  = new Vec3(0.0, 0.0, 0.0);
        $faces = [
            [new Vec3( 1.0,  0.0,  0.0), new Vec3(0.0, -1.0,  0.0)],
            [new Vec3(-1.0,  0.0,  0.0), new Vec3(0.0, -1.0,  0.0)],
            [new Vec3( 0.0,  1.0,  0.0), new Vec3(0.0,  0.0,  1.0)],
            [new Vec3( 0.0, -1.0,  0.0), new Vec3(0.0,  0.0, -1.0)],
            [new Vec3( 0.0,  0.0,  1.0), new Vec3(0.0, -1.0,  0.0)],
            [new Vec3( 0.0,  0.0, -1.0), new Vec3(0.0, -1.0,  0.0)],
        ];
        $out = [];
        foreach ($faces as [$forward, $up]) {
            $view = Mat4::lookAt($eye, $forward, $up);
            $out[] = $proj->multiply($view)->inverse()->toArray();
        }
        return $out;
    }

    /** Hash of every SetSky input that changes the rendered sky. */
    public static function skyHash(SetSky $sky): string
    {
        $md = $sky->moonDirection ?? new Vec3(0.0, -1.0, 0.0);
        return md5(pack(
            'f*',
            $sky->sunDirection->x, $sky->sunDirection->y, $sky->sunDirection->z, $sky->sunIntensity,
            $sky->sunColor->r, $sky->sunColor->g, $sky->sunColor->b, $sky->sunSize,
            $sky->zenithColor->r, $sky->zenithColor->g, $sky->zenithColor->b, $sky->sunGlowSize,
            $sky->horizonColor->r, $sky->horizonColor->g, $sky->horizonColor->b, $sky->sunGlowIntensity,
            $sky->groundColor->r, $sky->groundColor->g, $sky->groundColor->b, $sky->starBrightness,
            $md->x, $md->y, $md->z, $sky->moonIntensity,
            $sky->moonColor->r, $sky->moonColor->g, $sky->moonColor->b, $sky->cloudCover,
            $sky->cloudAltitude, $sky->cloudDensity, $sky->cloudWindSpeed, $sky->fogDensity,
            $sky->cloudWindDirection->x, $sky->cloudWindDirection->z,
        ));
    }

    public function release(): void
    {
        $this->cubemap = null;
        $this->target = null;
        $this->lastSkyHash = '';
    }
}
