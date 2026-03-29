<?php

declare(strict_types=1);

namespace PHPolygon\Rendering\Materials;

use PHPolygon\Rendering\Color;
use PHPolygon\Rendering\Material;
use PHPolygon\Rendering\MaterialRegistry;

/**
 * Wood material presets — planks, bamboo, driftwood, dark structural timber.
 *
 * Naming convention: all IDs start with "hut_wood" or "hut_door"/"hut_table" etc.
 * so the renderer automatically assigns proc_mode 7 (wood plank shader).
 *
 * Usage in game:
 *   WoodMaterials::registerAll();           // registers all presets
 *   WoodMaterials::plank();                 // returns Material without registering
 *   WoodMaterials::register('plank');       // registers single preset by key
 */
class WoodMaterials
{
    /** @var array<string, array{id: string, material: Material}> */
    private static ?array $cache = null;

    /**
     * All available wood material presets.
     *
     * @return array<string, array{id: string, material: Material}>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [
            // --- Planks (wall panels, floors) ---
            'plank' => [
                'id' => 'hut_wood',
                'material' => new Material(
                    albedo: Color::hex('#8B6B3E'),
                    roughness: 0.88,
                ),
            ],
            'plank_dark' => [
                'id' => 'hut_wood_dark',
                'material' => new Material(
                    albedo: Color::hex('#5C4422'),
                    roughness: 0.90,
                ),
            ],
            'plank_weathered' => [
                'id' => 'hut_wood_weathered',
                'material' => new Material(
                    albedo: Color::hex('#9E8A6A'),
                    roughness: 0.93,
                ),
            ],
            'plank_bleached' => [
                'id' => 'hut_wood_bleached',
                'material' => new Material(
                    albedo: Color::hex('#B8A880'),
                    roughness: 0.92,
                ),
            ],

            // --- Structural timber (posts, beams, rafters) ---
            'beam' => [
                'id' => 'hut_wood_beam',
                'material' => new Material(
                    albedo: Color::hex('#4A3520'),
                    roughness: 0.85,
                ),
            ],

            // --- Bamboo (railings, decorative) ---
            'bamboo' => [
                'id' => 'hut_wood_bamboo',
                'material' => new Material(
                    albedo: Color::hex('#A89050'),
                    roughness: 0.70,
                ),
            ],
            'bamboo_dark' => [
                'id' => 'hut_wood_bamboo_dark',
                'material' => new Material(
                    albedo: Color::hex('#7A6838'),
                    roughness: 0.75,
                ),
            ],

            // --- Driftwood (sun-bleached, grey) ---
            'driftwood' => [
                'id' => 'hut_wood_drift',
                'material' => new Material(
                    albedo: Color::hex('#A09888'),
                    roughness: 0.95,
                ),
            ],

            // --- Functional surfaces ---
            'floor' => [
                'id' => 'hut_floor',
                'material' => new Material(
                    albedo: Color::hex('#6B5530'),
                    roughness: 0.85,
                ),
            ],
            'door' => [
                'id' => 'hut_door',
                'material' => new Material(
                    albedo: Color::hex('#6B4E28'),
                    roughness: 0.82,
                ),
            ],
            'table' => [
                'id' => 'hut_table',
                'material' => new Material(
                    albedo: Color::hex('#7A5C34'),
                    roughness: 0.75,
                ),
            ],
            'chair' => [
                'id' => 'hut_chair',
                'material' => new Material(
                    albedo: Color::hex('#6B4E2A'),
                    roughness: 0.78,
                ),
            ],
            'window_frame' => [
                'id' => 'hut_window_frame',
                'material' => new Material(
                    albedo: Color::hex('#5A3E1E'),
                    roughness: 0.80,
                ),
            ],
        ];

        return self::$cache;
    }

    /**
     * Register all wood presets into the MaterialRegistry.
     */
    public static function registerAll(): void
    {
        foreach (self::all() as $preset) {
            MaterialRegistry::register($preset['id'], $preset['material']);
        }
    }

    /**
     * Register a single preset by key.
     */
    public static function register(string $key): void
    {
        $all = self::all();
        if (isset($all[$key])) {
            MaterialRegistry::register($all[$key]['id'], $all[$key]['material']);
        }
    }

    /**
     * Get the material ID for a preset key.
     */
    public static function id(string $key): string
    {
        return self::all()[$key]['id'] ?? '';
    }

    // --- Direct accessors ---

    public static function plank(): Material { return self::all()['plank']['material']; }
    public static function plankDark(): Material { return self::all()['plank_dark']['material']; }
    public static function plankWeathered(): Material { return self::all()['plank_weathered']['material']; }
    public static function plankBleached(): Material { return self::all()['plank_bleached']['material']; }
    public static function beam(): Material { return self::all()['beam']['material']; }
    public static function bamboo(): Material { return self::all()['bamboo']['material']; }
    public static function bambooDark(): Material { return self::all()['bamboo_dark']['material']; }
    public static function driftwood(): Material { return self::all()['driftwood']['material']; }
    public static function floor(): Material { return self::all()['floor']['material']; }
    public static function door(): Material { return self::all()['door']['material']; }
    public static function table(): Material { return self::all()['table']['material']; }
    public static function chair(): Material { return self::all()['chair']['material']; }
    public static function windowFrame(): Material { return self::all()['window_frame']['material']; }
}
