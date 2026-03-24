<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Testing;

use PHPUnit\Framework\TestCase;
use PHPolygon\Component\Transform2D;
use PHPolygon\Component\SpriteRenderer;
use PHPolygon\Engine;
use PHPolygon\Math\Vec2;
use PHPolygon\Rendering\Color;
use PHPolygon\Scene\Scene;
use PHPolygon\Scene\SceneBuilder;
use PHPolygon\Testing\GdRenderer2D;
use PHPolygon\Testing\NullTextureManager;
use PHPolygon\Testing\VisualTestCase;

// --- Example game scenes ---

class MainMenuScene extends Scene
{
    public function getName(): string
    {
        return 'main-menu';
    }

    public function build(SceneBuilder $builder): void
    {
        $builder->entity('background')
            ->with(new Transform2D(position: new Vec2(400, 300)))
            ->with(new SpriteRenderer(textureId: 'bg_menu', width: 800, height: 600));

        $builder->entity('logo')
            ->with(new Transform2D(position: new Vec2(400, 120)))
            ->with(new SpriteRenderer(textureId: 'logo', width: 240, height: 80));

        $builder->entity('play-button')
            ->with(new Transform2D(position: new Vec2(400, 300)))
            ->with(new SpriteRenderer(textureId: 'btn_play', width: 180, height: 50));

        $builder->entity('settings-button')
            ->with(new Transform2D(position: new Vec2(400, 370)))
            ->with(new SpriteRenderer(textureId: 'btn_settings', width: 180, height: 50));

        $builder->entity('quit-button')
            ->with(new Transform2D(position: new Vec2(400, 440)))
            ->with(new SpriteRenderer(textureId: 'btn_quit', width: 180, height: 50));
    }
}

class GameplayScene extends Scene
{
    public function getName(): string
    {
        return 'gameplay';
    }

    public function build(SceneBuilder $builder): void
    {
        $builder->entity('ground')
            ->with(new Transform2D(position: new Vec2(400, 550)))
            ->with(new SpriteRenderer(textureId: 'ground', width: 800, height: 100));

        $builder->entity('player')
            ->with(new Transform2D(position: new Vec2(120, 480)))
            ->with(new SpriteRenderer(textureId: 'player', width: 32, height: 48));

        $builder->entity('enemy-1')
            ->with(new Transform2D(position: new Vec2(500, 490)))
            ->with(new SpriteRenderer(textureId: 'enemy_slime', width: 28, height: 28));

        $builder->entity('enemy-2')
            ->with(new Transform2D(position: new Vec2(650, 490)))
            ->with(new SpriteRenderer(textureId: 'enemy_slime', width: 28, height: 28));

        $builder->entity('coin-1')
            ->with(new Transform2D(position: new Vec2(300, 420)))
            ->with(new SpriteRenderer(textureId: 'coin', width: 16, height: 16));

        $builder->entity('coin-2')
            ->with(new Transform2D(position: new Vec2(350, 380)))
            ->with(new SpriteRenderer(textureId: 'coin', width: 16, height: 16));
    }
}

// --- Tests ---

class SceneRenderingTest extends TestCase
{
    use VisualTestCase;

    /**
     * Register dummy textures with realistic sizes so sprites render
     * as visible placeholder rectangles through the Renderer2DSystem.
     */
    private function registerTestTextures(Engine $engine): void
    {
        $tm = $engine->textures;
        assert($tm instanceof NullTextureManager);

        // Main menu assets
        $tm->register('bg_menu', 800, 600);
        $tm->register('logo', 240, 80);
        $tm->register('btn_play', 180, 50);
        $tm->register('btn_settings', 180, 50);
        $tm->register('btn_quit', 180, 50);

        // Gameplay assets
        $tm->register('ground', 800, 100);
        $tm->register('player', 32, 48);
        $tm->register('enemy_slime', 28, 28);
        $tm->register('coin', 16, 16);
    }

    public function testMainMenuLayout(): void
    {
        [$engine, $renderer] = $this->createVisualTestEngine(800, 600);
        $this->registerTestTextures($engine);
        $this->addRenderSystem($engine, $renderer);

        $engine->scenes->register('main-menu', MainMenuScene::class);
        $engine->scenes->loadScene('main-menu');
        $engine->world->update(1.0 / 60.0);

        $renderer->beginFrame();
        $renderer->clear(new Color(0.08, 0.05, 0.15));
        $engine->world->render();
        $renderer->endFrame();

        // Verify entities exist
        $entities = $engine->scenes->getSceneEntities('main-menu');
        $this->assertNotNull($entities);
        $this->assertCount(5, $entities);

        $this->assertScreenshot($renderer, 'main-menu');
    }

    public function testGameplayLayout(): void
    {
        [$engine, $renderer] = $this->createVisualTestEngine(800, 600);
        $this->registerTestTextures($engine);
        $this->addRenderSystem($engine, $renderer);

        $engine->scenes->register('gameplay', GameplayScene::class);
        $engine->scenes->loadScene('gameplay');
        $engine->world->update(1.0 / 60.0);

        $renderer->beginFrame();
        $renderer->clear(new Color(0.4, 0.6, 0.9)); // sky blue
        $engine->world->render();
        $renderer->endFrame();

        $entities = $engine->scenes->getSceneEntities('gameplay');
        $this->assertNotNull($entities);
        $this->assertArrayHasKey('player', $entities);
        $this->assertArrayHasKey('ground', $entities);

        $this->assertScreenshot($renderer, 'gameplay');
    }

    public function testSceneTransition(): void
    {
        [$engine, $renderer] = $this->createVisualTestEngine(800, 600);
        $this->registerTestTextures($engine);
        $this->addRenderSystem($engine, $renderer);

        // Load main menu
        $engine->scenes->register('main-menu', MainMenuScene::class);
        $engine->scenes->register('gameplay', GameplayScene::class);
        $engine->scenes->loadScene('main-menu');

        $this->assertTrue($engine->scenes->isLoaded('main-menu'));
        $this->assertFalse($engine->scenes->isLoaded('gameplay'));

        // Transition to gameplay (Single mode unloads previous)
        $engine->scenes->loadScene('gameplay');

        $this->assertFalse($engine->scenes->isLoaded('main-menu'));
        $this->assertTrue($engine->scenes->isLoaded('gameplay'));
        $this->assertSame('gameplay', $engine->scenes->getActiveSceneName());

        $engine->world->update(1.0 / 60.0);

        $renderer->beginFrame();
        $renderer->clear(new Color(0.4, 0.6, 0.9));
        $engine->world->render();
        $renderer->endFrame();

        $this->assertScreenshot($renderer, 'after-transition');
    }

    private function addRenderSystem(Engine $engine, GdRenderer2D $renderer): void
    {
        // Position camera at screen center so world coords match pixel coords
        $engine->camera2D->position = new Vec2(
            $engine->camera2D->getViewportWidth() / 2.0,
            $engine->camera2D->getViewportHeight() / 2.0,
        );

        $engine->world->addSystem(new \PHPolygon\System\Renderer2DSystem(
            $renderer,
            $engine->camera2D,
            $engine->textures,
        ));
    }
}
