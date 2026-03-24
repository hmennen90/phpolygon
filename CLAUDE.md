# CLAUDE.md — PHPolygon Engine

PHP-native game engine with AI-first authoring. This file governs how Claude Code
works in this repository. Read it fully before writing any code.

---

## Engine identity

**PHPolygon** is a standalone PHP-native game engine. The primary authoring tool
is Claude Code. The primary render backend is OpenGL 4.1 via php-glfw/NanoVG
for 2D, Vulkan via php-vulkan for 3D (Phase 6).

Games are built in separate repositories and require `phpolygon/phpolygon`
via Composer.

---

## Architecture decisions (settled — do not revisit without explicit instruction)

### ECS: Hybrid model
- **Entities** are PHP objects with a component array. They have identity and lifecycle.
- **Components** own *per-entity* behaviour: `onAttach()`, `onUpdate()`, `onDetach()`,
  `onInspectorGUI()`. They may hold data and per-entity logic.
- **Systems** own *cross-entity* logic: physics, collision, economy, pathfinding.
  A System iterates components across multiple entities.
- **Discipline rule:** never put cross-entity logic in a Component. Never put
  per-entity render or state logic in a System. When in doubt, ask which boundary
  the code crosses.

### Scene authoring: PHP-canonical / split
- **PHP is always the canonical source of truth** for scene structure (entities,
  components, configuration).
- **JSON is the intermediate format** for the Vue/NativePHP editor. It is generated
  from PHP and consumed by the editor. The editor writes JSON back; a bidirectional
  transpiler converts to/from PHP.
- **Runtime state** (save games, dynamic positions, live game state) is always JSON.
- Use PHP 8.x `#[Attribute]` annotations to drive serialisation via Reflection.
  New components must never implement manual `toJson()` / `fromJson()` methods —
  the serialiser handles this automatically.
- Scene PHP files are version-controlled as code. JSON files are derived artefacts.

### Render interface: Layered
- `RenderContextInterface` — base: `beginFrame()`, `endFrame()`, `clear()`,
  `setViewport()`
- `Renderer2D extends RenderContextInterface` — NanoVG backend for 2D games
- `Renderer3D extends RenderContextInterface` — Vulkan backend for 3D games

`Renderer3D` uses a **Command Buffer abstraction** from day one. PHP builds a
`RenderCommandList`; the backend (Vulkan) executes it. The OpenGL 3D backend
(if ever needed) emulates command buffers. Do not design `Renderer3D` around
the OpenGL state-machine model.

### GPU backends
| Backend | Status | Target |
|---|---|---|
| OpenGL 4.1 via php-glfw | Active | 2D games, all phases |
| Vulkan via php-vulkan | Phase 6 | 3D games |
| D3D11 / D3D12 | Cancelled | — |
| Metal | Not planned | — |

**D3D is permanently out of scope.** Do not add D3D stubs, interfaces, or comments.
Vulkan covers Windows natively; MoltenVK covers macOS.

### Shaders
- Authoring language: **GLSL** (human- and AI-readable plaintext)
- Compiled to **SPIR-V** at build time via `glslangValidator` or `shaderc`
- SPIR-V binaries are committed to `assets/shaders/compiled/`
- Claude Code writes GLSL; the build step produces SPIR-V. Never write SPIR-V by hand.

### Editor
- The editor is a **NativePHP desktop application** (Electron wrapper + Vue SPA).
- It has **direct filesystem access** to project directories — no HTTP server, no IPC.
- Multiple game projects are opened as workspaces (Unity-style), each in its own
  directory.
- The Game Loop runs **on demand** inside the editor's play mode. It is not
  continuously running.
- The editor is a **data editor**, not a real-time viewport. The game renders in a
  separate native OpenGL/Vulkan window when play mode is active.
- The transpiler is called directly by the editor process — no network boundary.

---

## Naming conventions

| Concept | Convention | Example |
|---|---|---|
| Engine namespace | `PHPolygon\` | `PHPolygon\ECS\Entity` |
| Component classes | Noun, no suffix | `MeshRenderer`, `BoxCollider2D` |
| System classes | Noun + `System` | `EconomySystem`, `PhysicsSystem` |
| Events | Past tense noun | `EntitySpawned`, `SceneLoaded` |
| Interfaces | `*Interface` | `RenderContextInterface` |
| JSON scene files | `snake_case.scene.json` | `main_menu.scene.json` |
| PHP scene files | `PascalCase.php` | `MainMenu.php` |
| Shader source | `name.vert.glsl` / `name.frag.glsl` | `terrain.vert.glsl` |
| Compiled shaders | `name.vert.spv` / `name.frag.spv` | `terrain.vert.spv` |

---

## Anti-patterns — never do these

- **Do not** put cross-entity logic in a Component method.
- **Do not** implement `toJson()` / `fromJson()` manually on Components — use Attributes.
- **Do not** design `Renderer3D` around OpenGL state-machine patterns.
- **Do not** add D3D, Metal, or Vulkan stubs inside `Renderer2D`.
- **Do not** start a built-in HTTP server for editor communication — the editor has
  direct filesystem access.
- **Do not** modify `game.phar` or compiled SPIR-V by hand.
- **Do not** store runtime game state in PHP files — use JSON.
- **Do not** use FFI for frame-critical calls (e.g. `SteamAPI_RunCallbacks()`) —
  use native C-extensions.

---

## C-extensions (available, do not reimplement in PHP)

| Extension | Purpose | Status |
|---|---|---|
| php-glfw | OpenGL 4.1 + NanoVG (2D rendering) | Active |
| php-vulkan | Vulkan (3D rendering) | Available, Phase 6 |
| php-steamworks | Steamworks SDK integration | Published on Packagist |

When writing engine code that touches GPU, Steam, or audio — use the extension.
Do not wrap extension calls in FFI unless there is an explicit reason.

---

## Distribution model

```
codetycoon          ← native launcher binary (C/Go/Rust)
runtime/php         ← static PHP binary (static-php-cli, includes all extensions)
game.phar           ← engine + game logic, Opcache bytecode, not human-readable
assets/             ← open: sprites, sounds, JSON scenes, UI layouts
saves/              ← user data: JSON save files
mods/               ← open: PHP + assets, scanned by ModLoader
```

- Game core ships as PHAR with Opcache bytecode (comparable protection to C# IL).
- `mods/` is intentionally open — modders and Claude Code use the same tools.
- Build target: macOS `.app`/DMG, Linux AppImage, Windows installer, Steam depot.

---

## AI authoring workflow

Claude Code is the primary authoring tool. When generating content:

1. **Scenes** — write PHP files (canonical). JSON is derived by the transpiler.
2. **Components** — PHP classes with `#[Component]` attribute, Lifecycle hooks.
3. **UI layouts** — JSON (transpiled to PHP at dev time, zero runtime parser overhead).
4. **Game logic** — PHP Systems and Components.
5. **Shaders** — GLSL source files in `assets/shaders/source/`.
6. **Physics materials** — JSON definitions in `assets/physics/`.
7. **Mods** — `mod.json` + PHP class implementing `ModInterface` + assets.

Every generated file is a Git commit. Every step is reviewable. No black-box state.

---

## Build system

The build pipeline (`src/Build/`) compiles a game project into a standalone
executable. CLI entry point: `bin/phpolygon`.

### Usage

```bash
php -d phar.readonly=0 vendor/bin/phpolygon build                # auto-detect platform
php -d phar.readonly=0 vendor/bin/phpolygon build macos-arm64     # specific target
php -d phar.readonly=0 vendor/bin/phpolygon build all              # every platform
php vendor/bin/phpolygon build --dry-run                           # show config only
```

### 7-phase pipeline

1. **Vendor** — `composer update --no-dev` (restored after build)
2. **Stage** — copy src/, vendor/, assets/, resources/ into temp dir, resolve
   symlinks, exclude tests/docs/editor via glob patterns
3. **PHAR** — create game.phar with a custom stub that handles micro SAPI
   detection, macOS .app bundle paths, resource extraction, and engine bootstrap
4. **micro.sfx** — resolve static PHP binary (explicit path → cache
   `~/.phpolygon/build-cache/` → download from GitHub Release)
5. **Combine** — concatenate micro.sfx + game.phar into single executable
6. **Package** — platform-specific: macOS `.app` bundle with Info.plist,
   Linux/Windows flat directory
7. **Report** — PHAR size, binary size, bundle size

### Configuration

`build.json` in game project root (optional, falls back to composer.json):

```json
{
  "name": "MyGame",
  "identifier": "com.studio.mygame",
  "version": "1.0.0",
  "entry": "game.php",
  "run": "\\App\\Game::start();",
  "php": { "extensions": ["glfw", "mbstring", "zip", "phar"] },
  "phar": { "exclude": ["**/tests", "**/docs"] },
  "resources": { "external": ["resources/audio"] },
  "platforms": {
    "macos": { "icon": "icon.icns", "minimumVersion": "12.0" }
  }
}
```

### Build classes

| Class | Purpose |
|---|---|
| `BuildConfig` | Loads build.json + composer.json, provides all settings |
| `PharBuilder` | Stages sources, builds PHAR with custom stub |
| `StaticPhpResolver` | Finds/downloads/caches micro.sfx binary |
| `PlatformPackager` | Creates .app bundle, Linux dir, Windows .exe |
| `GameBuilder` | Orchestrates the 7-phase pipeline |

### PHAR stub constants

The stub defines these at runtime:
- `PHPOLYGON_PATH_ROOT` — resource base directory
- `PHPOLYGON_PATH_ASSETS` — extracted assets
- `PHPOLYGON_PATH_RESOURCES` — extracted resources
- `PHPOLYGON_PATH_SAVES` — user save data
- `PHPOLYGON_PATH_MODS` — mod directory

---

## Headless mode

The engine can run without a GPU, display server, or OpenGL context.
This enables CI testing, scene validation, and visual regression testing.

```php
$engine = new Engine(new EngineConfig(headless: true));
// All subsystems work: ECS, Scenes, Events, Audio, Locale, Saves
```

### How it works

| Normal mode | Headless mode |
|---|---|
| `Window` (GLFW) | `NullWindow` (no-op, configurable dimensions) |
| `Renderer2D` (NanoVG/OpenGL) | `NullRenderer2D` (accepts all draws, no output) |
| `TextureManager` (GL textures) | `NullTextureManager` (dummy textures with dimensions) |

The `headless` flag in `EngineConfig` switches all three automatically.
`NullWindow` extends `Window`, `NullTextureManager` extends `TextureManager` —
existing code that type-hints against the base classes works unchanged.

### Null objects

- `NullWindow` — returns configured width/height, `shouldClose()` returns false
  until `requestClose()` is called, all other methods are no-ops
- `NullRenderer2D` — implements `Renderer2DInterface`, every draw method is a no-op
- `NullTextureManager` — `load()` auto-creates dummy `Texture` objects with
  `glId: 0` and configurable width/height; `register(id, w, h)` pre-registers
  textures for tests that need specific dimensions

---

## Testing and visual regression testing (VRT)

### Test infrastructure (`src/Testing/`)

| Class | Purpose |
|---|---|
| `GdRenderer2D` | Software renderer using PHP GD — draws to `GdImage`, no GPU |
| `ScreenshotComparer` | Pixel-level comparison using YIQ color space (Pixelmatch algorithm) |
| `ComparisonResult` | Result object with `passes()`, tolerances, diff path |
| `VisualTestCase` | PHPUnit trait — Playwright-style `assertScreenshot()` |
| `NullTextureManager` | Headless texture stubs for scene rendering tests |

### VRT workflow (Playwright-style)

```php
class MyGameTest extends TestCase {
    use VisualTestCase;

    public function testMainMenu(): void {
        $renderer = new GdRenderer2D(800, 600);
        $renderer->beginFrame();
        // ... draw scene ...
        $renderer->endFrame();

        $this->assertScreenshot($renderer, 'main-menu');
    }
}
```

- **First run:** saves reference screenshot → test passes
- **Subsequent runs:** compares against reference → fails on visual diff
- **Update snapshots:** `PHPOLYGON_UPDATE_SNAPSHOTS=1 vendor/bin/phpunit`

### Snapshot file structure

```
tests/MyTest.php
tests/MyTest.php-snapshots/
├── main-menu.png                    ← reference (no platform suffix by default)
├── main-menu.actual.png             ← only on failure
└── main-menu.diff.png               ← only on failure (red = mismatch)
```

Default: **no platform suffix**. Override `usePlatformSuffix()` → `true` for
font-dependent tests, which produces `name-gd-darwin.png` / `name-gd-linux.png`.

### Scene rendering tests

Use `renderScene()` or `createVisualTestEngine()` to load game scenes headlessly:

```php
// Quick: load scene, tick, render, screenshot
[$engine, $renderer] = $this->renderScene(MainMenuScene::class, 'main-menu');
$this->assertScreenshot($renderer, 'main-menu');

// Full control: register textures, custom camera, multiple ticks
[$engine, $renderer] = $this->createVisualTestEngine(800, 600);
$tm = $engine->textures; // NullTextureManager in headless
$tm->register('player', 32, 48);
$tm->register('ground', 800, 100);
// ... load scene, add render system, tick, render ...
$this->assertScreenshot($renderer, 'gameplay');
```

### Comparison parameters

```php
$this->assertScreenshot($renderer, 'name',
    threshold: 0.1,          // per-pixel YIQ tolerance (0.0–1.0)
    maxDiffPixels: 50,       // absolute pixel count tolerance
    maxDiffPixelRatio: 0.01, // ratio tolerance (1% of pixels)
    mask: [                  // ignore dynamic regions (filled magenta)
        ['x' => 10, 'y' => 10, 'w' => 100, 'h' => 20],
    ],
);
```

### Fonts

```php
// Place .ttf files in resources/fonts/
$renderer->loadFont('inter', 'resources/fonts/Inter-Regular.ttf');
$renderer->setFont('inter');
$renderer->drawText('Score: 42,000', 20, 20, 24, Color::white());
```

Works identically for `Renderer2D` (NanoVG) and `GdRenderer2D` (GD/FreeType).
Font rendering may differ between platforms — use `usePlatformSuffix() → true`
for font-dependent VRT tests.

### GdRenderer2D capabilities

The GD software renderer supports: filled/outlined rectangles, rounded rects,
circles, lines, text (TrueType via `imagettftext`), centered text, word-wrapped
text, transform stack (`pushTransform`/`popTransform` via Mat3), scissor stack,
and sprite placeholders (grey rectangles with outlines for textures).

It does **not** produce pixel-identical output to the OpenGL `Renderer2D` — it is
a structural approximation for layout and regression testing, not a reference
renderer.
