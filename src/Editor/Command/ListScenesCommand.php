<?php

declare(strict_types=1);

namespace PHPolygon\Editor\Command;

use PHPolygon\Editor\EditorContext;

class ListScenesCommand implements CommandInterface
{
    public function __construct(private readonly array $args = []) {}

    public function execute(EditorContext $context): array
    {
        $scenesDir = $context->getScenesDir();
        $scenes = [];

        if (is_dir($scenesDir)) {
            $iterator = new \DirectoryIterator($scenesDir);
            foreach ($iterator as $file) {
                if ($file->getExtension() === 'php' && !$file->isDot()) {
                    $scenes[] = $file->getBasename('.php');
                }
            }
            sort($scenes);
        }

        return ['scenes' => $scenes];
    }
}
