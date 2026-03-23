<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPolygon\Editor\Command\EditorCommandBus;
use PHPolygon\Editor\EditorContext;
use PHPolygon\Editor\Project\ProjectLoader;
use PHPolygon\Editor\Registry\ComponentRegistry;

class EditorApiController extends Controller
{
    public function dispatch(Request $request, EditorCommandBus $bus): JsonResponse
    {
        $request->validate([
            'command' => 'required|string',
            'args' => 'array',
        ]);

        try {
            $result = $bus->dispatch(
                $request->input('command'),
                $request->input('args', []),
            );

            return response()->json(['ok' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function project(EditorContext $context): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'manifest' => $context->manifest->toArray(),
                'projectDir' => $context->projectDir,
                'opened' => $context->projectDir !== '',
            ],
        ]);
    }

    public function openProject(
        Request $request,
        ProjectLoader $loader,
        ComponentRegistry $registry,
    ): JsonResponse {
        $request->validate(['dir' => 'required|string']);

        $dir = $request->input('dir');

        if (!is_dir($dir)) {
            return response()->json(['ok' => false, 'error' => "Directory not found: {$dir}"], 422);
        }

        try {
            $manifest = $loader->load($dir);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        // Store in session
        session(['editor_project_dir' => $dir]);

        // Scan components from project's PSR-4 roots
        foreach ($manifest->psr4Roots as $namespace => $path) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $path;
            if (is_dir($fullPath)) {
                $registry->scan($fullPath, $namespace);
            }
        }

        // Also scan engine's built-in components
        $engineComponentDir = dirname(__DIR__, 3) . '/vendor/phpolygon/phpolygon/src/Component';
        if (is_dir($engineComponentDir)) {
            $registry->scan($engineComponentDir, 'PHPolygon\\Component\\');
        }

        // Clear singletons to rebuild with new project
        app()->forgetInstance(EditorContext::class);
        app()->forgetInstance(EditorCommandBus::class);

        return response()->json([
            'ok' => true,
            'data' => [
                'manifest' => $manifest->toArray(),
                'components' => $registry->toArray(),
            ],
        ]);
    }

    public function assets(Request $request, EditorContext $context): JsonResponse
    {
        $subPath = $request->input('path', '');
        $assetsDir = $context->getAssetsDir();
        $fullPath = $subPath ? $assetsDir . DIRECTORY_SEPARATOR . $subPath : $assetsDir;

        if (!is_dir($fullPath)) {
            return response()->json(['ok' => true, 'data' => ['files' => [], 'path' => $subPath]]);
        }

        $files = [];
        $iterator = new \DirectoryIterator($fullPath);
        foreach ($iterator as $file) {
            if ($file->isDot()) continue;
            $files[] = [
                'name' => $file->getFilename(),
                'isDir' => $file->isDir(),
                'size' => $file->isFile() ? $file->getSize() : 0,
                'ext' => $file->isFile() ? $file->getExtension() : null,
            ];
        }

        usort($files, fn($a, $b) => ($b['isDir'] <=> $a['isDir']) ?: strcmp($a['name'], $b['name']));

        return response()->json(['ok' => true, 'data' => ['files' => $files, 'path' => $subPath]]);
    }
}
