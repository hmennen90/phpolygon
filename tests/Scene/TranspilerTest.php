<?php

declare(strict_types=1);

namespace PHPolygon\Tests\Scene;

use PHPUnit\Framework\TestCase;
use PHPolygon\Component\Camera2DComponent;
use PHPolygon\Component\SpriteRenderer;
use PHPolygon\Component\Transform2D;
use PHPolygon\Math\Vec2;
use PHPolygon\Rendering\Color;
use PHPolygon\Scene\Scene;
use PHPolygon\Scene\SceneBuilder;
use PHPolygon\Scene\SceneConfig;
use PHPolygon\Scene\Transpiler\JsonSceneFormat;
use PHPolygon\Scene\Transpiler\SceneTranspiler;
use PHPolygon\System\Camera2DSystem;
use PHPolygon\System\Renderer2DSystem;

class SampleScene extends Scene
{
    public function getName(): string
    {
        return 'sample_scene';
    }

    public function getConfig(): SceneConfig
    {
        return new SceneConfig(
            clearColor: Color::hex('#2a2a4a'),
        );
    }

    public function getSystems(): array
    {
        return [Camera2DSystem::class, Renderer2DSystem::class];
    }

    public function build(SceneBuilder $builder): void
    {
        $builder->entity('Camera')
            ->with(new Transform2D())
            ->with(new Camera2DComponent());

        $builder->entity('Player')
            ->with(new Transform2D(position: new Vec2(100, 200)))
            ->with(new SpriteRenderer(textureId: 'player_idle'))
            ->child('Weapon')
                ->with(new Transform2D(position: new Vec2(20, 0)))
                ->with(new SpriteRenderer(textureId: 'sword'));
    }
}

class TranspilerTest extends TestCase
{
    private SceneTranspiler $transpiler;

    protected function setUp(): void
    {
        $this->transpiler = new SceneTranspiler();
    }

    public function testToArrayProducesValidStructure(): void
    {
        $scene = new SampleScene();
        $data = $this->transpiler->toArray($scene);

        $this->assertSame(JsonSceneFormat::VERSION, $data['_version']);
        $this->assertSame(SampleScene::class, $data['_scene']);
        $this->assertSame('sample_scene', $data['name']);
        $this->assertIsArray($data['config']);
        $this->assertIsArray($data['systems']);
        $this->assertIsArray($data['entities']);
    }

    public function testToArrayEntities(): void
    {
        $scene = new SampleScene();
        $data = $this->transpiler->toArray($scene);

        $this->assertCount(2, $data['entities']);

        // Camera entity
        $camera = $data['entities'][0];
        $this->assertSame('Camera', $camera['name']);
        $this->assertCount(2, $camera['components']);
        $this->assertSame(Transform2D::class, $camera['components'][0]['_class']);
        $this->assertSame(Camera2DComponent::class, $camera['components'][1]['_class']);

        // Player entity with child
        $player = $data['entities'][1];
        $this->assertSame('Player', $player['name']);
        $this->assertArrayHasKey('children', $player);
        $this->assertCount(1, $player['children']);

        $weapon = $player['children'][0];
        $this->assertSame('Weapon', $weapon['name']);
    }

    public function testToJsonReturnsValidJson(): void
    {
        $scene = new SampleScene();
        $json = $this->transpiler->toJson($scene);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('sample_scene', $decoded['name']);
    }

    public function testJsonFormatValidation(): void
    {
        $valid = [
            'name' => 'test',
            'entities' => [
                ['name' => 'A', 'components' => [['_class' => 'Foo']]],
            ],
        ];
        JsonSceneFormat::validate($valid);
        $this->assertTrue(true); // No exception

        $this->expectException(\RuntimeException::class);
        JsonSceneFormat::validate(['entities' => []]); // Missing name
    }

    public function testFromArrayGeneratesPhpCode(): void
    {
        $scene = new SampleScene();
        $data = $this->transpiler->toArray($scene);
        $php = $this->transpiler->fromArray($data);

        $this->assertStringContainsString('class SampleScene extends Scene', $php);
        $this->assertStringContainsString("return 'sample_scene'", $php);
        $this->assertStringContainsString('->entity(\'Camera\')', $php);
        $this->assertStringContainsString('->entity(\'Player\')', $php);
        $this->assertStringContainsString('->child(\'Weapon\')', $php);
        $this->assertStringContainsString('new Transform2D(', $php);
        $this->assertStringContainsString('new SpriteRenderer(', $php);
    }

    public function testFromArrayIncludesUseStatements(): void
    {
        $scene = new SampleScene();
        $data = $this->transpiler->toArray($scene);
        $php = $this->transpiler->fromArray($data);

        $this->assertStringContainsString('use PHPolygon\\Scene\\Scene;', $php);
        $this->assertStringContainsString('use PHPolygon\\Scene\\SceneBuilder;', $php);
        $this->assertStringContainsString('use PHPolygon\\Component\\Transform2D;', $php);
    }

    public function testFromArrayIncludesSystems(): void
    {
        $scene = new SampleScene();
        $data = $this->transpiler->toArray($scene);
        $php = $this->transpiler->fromArray($data);

        $this->assertStringContainsString('getSystems(): array', $php);
        $this->assertStringContainsString('Camera2DSystem::class', $php);
        $this->assertStringContainsString('Renderer2DSystem::class', $php);
    }

    public function testRoundtripPreservesStructure(): void
    {
        $scene = new SampleScene();
        $data = $this->transpiler->toArray($scene);
        $json = json_encode($data);
        $restored = json_decode($json, true);

        $this->assertSame($data['name'], $restored['name']);
        $this->assertSame(count($data['entities']), count($restored['entities']));
        $this->assertSame(
            $data['entities'][0]['components'][0]['_class'],
            $restored['entities'][0]['components'][0]['_class'],
        );
    }
}
