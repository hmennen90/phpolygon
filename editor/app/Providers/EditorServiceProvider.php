<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PHPolygon\Editor\Command\AddComponentCommand;
use PHPolygon\Editor\Command\CreateEntityCommand;
use PHPolygon\Editor\Command\DeleteEntityCommand;
use PHPolygon\Editor\Command\EditorCommandBus;
use PHPolygon\Editor\Command\GetComponentSchemaCommand;
use PHPolygon\Editor\Command\GetEntityHierarchyCommand;
use PHPolygon\Editor\Command\ListComponentsCommand;
use PHPolygon\Editor\Command\ListScenesCommand;
use PHPolygon\Editor\Command\LoadSceneCommand;
use PHPolygon\Editor\Command\RedoCommand;
use PHPolygon\Editor\Command\RemoveComponentCommand;
use PHPolygon\Editor\Command\RenameEntityCommand;
use PHPolygon\Editor\Command\ReparentEntityCommand;
use PHPolygon\Editor\Command\SaveSceneCommand;
use PHPolygon\Editor\Command\UndoCommand;
use PHPolygon\Editor\Command\UpdatePropertyCommand;
use PHPolygon\Editor\EditorContext;
use PHPolygon\Editor\Project\ProjectLoader;
use PHPolygon\Editor\Project\ProjectManifest;
use PHPolygon\Editor\Registry\ComponentRegistry;
use PHPolygon\Editor\Registry\SystemRegistry;
use PHPolygon\Scene\Transpiler\SceneTranspiler;

class EditorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ComponentRegistry::class);
        $this->app->singleton(SystemRegistry::class);
        $this->app->singleton(SceneTranspiler::class);
        $this->app->singleton(ProjectLoader::class);

        $this->app->singleton(EditorContext::class, function ($app) {
            $projectDir = session('editor_project_dir', '');
            $manifest = new ProjectManifest(
                name: 'Untitled',
                version: '0.0.0',
                engineVersion: '*',
                scenesPath: 'src/Scene',
                assetsPath: 'assets',
                psr4Roots: [],
                entryScene: '',
            );

            if ($projectDir && file_exists($projectDir . '/phpolygon.project.json')) {
                $manifest = $app->make(ProjectLoader::class)->load($projectDir);
            }

            return new EditorContext(
                manifest: $manifest,
                components: $app->make(ComponentRegistry::class),
                systems: $app->make(SystemRegistry::class),
                transpiler: $app->make(SceneTranspiler::class),
                projectDir: $projectDir,
            );
        });

        $this->app->singleton(EditorCommandBus::class, function ($app) {
            $bus = new EditorCommandBus($app->make(EditorContext::class));

            $bus->register('list_components', ListComponentsCommand::class);
            $bus->register('get_component_schema', GetComponentSchemaCommand::class);
            $bus->register('load_scene', LoadSceneCommand::class);
            $bus->register('save_scene', SaveSceneCommand::class);
            $bus->register('list_scenes', ListScenesCommand::class);
            $bus->register('create_entity', CreateEntityCommand::class);
            $bus->register('delete_entity', DeleteEntityCommand::class);
            $bus->register('add_component', AddComponentCommand::class);
            $bus->register('remove_component', RemoveComponentCommand::class);
            $bus->register('update_property', UpdatePropertyCommand::class);
            $bus->register('get_entity_hierarchy', GetEntityHierarchyCommand::class);
            $bus->register('undo', UndoCommand::class);
            $bus->register('redo', RedoCommand::class);
            $bus->register('rename_entity', RenameEntityCommand::class);
            $bus->register('reparent_entity', ReparentEntityCommand::class);

            return $bus;
        });
    }

    public function boot(): void
    {
        //
    }
}
