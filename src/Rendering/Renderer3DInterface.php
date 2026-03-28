<?php

declare(strict_types=1);

namespace PHPolygon\Rendering;

interface Renderer3DInterface extends RenderContextInterface
{
    public function render(RenderCommandList $commandList): void;
}
