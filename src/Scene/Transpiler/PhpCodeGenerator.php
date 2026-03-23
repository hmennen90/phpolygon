<?php

declare(strict_types=1);

namespace PHPolygon\Scene\Transpiler;

use PHPolygon\ECS\Attribute\Property;
use PHPolygon\ECS\Attribute\Serializable;
use PHPolygon\ECS\Serializer\AttributeSerializer;
use PHPolygon\Math\Vec2;
use PHPolygon\Math\Vec3;
use PHPolygon\Rendering\Color;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

class PhpCodeGenerator
{
    public function __construct(
        private readonly AttributeSerializer $serializer,
    ) {}

    /**
     * Generate PHP Scene source code from a JSON-decoded scene array.
     *
     * @param array<string, mixed> $data
     */
    public function generate(array $data): string
    {
        $sceneName = $data['name'];
        $className = $this->nameToClassName($sceneName);
        $sceneClass = $data['_scene'] ?? null;
        $namespace = $sceneClass ? $this->extractNamespace($sceneClass) : 'App\\Scene';

        $systems = $data['systems'] ?? [];
        $entities = $data['entities'] ?? [];
        $config = $data['config'] ?? null;

        // Collect all use statements
        $uses = $this->collectUseStatements($entities, $systems, $config);

        $code = "<?php\n\n";
        $code .= "declare(strict_types=1);\n\n";
        $code .= "namespace {$namespace};\n\n";

        foreach ($uses as $use) {
            $code .= "use {$use};\n";
        }
        $code .= "\n";

        $code .= "class {$className} extends Scene\n";
        $code .= "{\n";

        // getName()
        $code .= "    public function getName(): string\n";
        $code .= "    {\n";
        $code .= "        return " . var_export($sceneName, true) . ";\n";
        $code .= "    }\n\n";

        // getConfig() — only if non-default
        if ($config !== null) {
            $configCode = $this->generateConfigMethod($config);
            if ($configCode !== null) {
                $code .= $configCode . "\n";
            }
        }

        // getSystems()
        if (!empty($systems)) {
            $code .= "    public function getSystems(): array\n";
            $code .= "    {\n";
            $code .= "        return [\n";
            foreach ($systems as $system) {
                $short = $this->shortName($system);
                $code .= "            {$short}::class,\n";
            }
            $code .= "        ];\n";
            $code .= "    }\n\n";
        }

        // build()
        $code .= "    public function build(SceneBuilder \$builder): void\n";
        $code .= "    {\n";
        foreach ($entities as $entity) {
            $code .= $this->generateEntityCode($entity, '        ', '$builder');
            $code .= "\n";
        }
        $code .= "    }\n";

        $code .= "}\n";

        return $code;
    }

    /**
     * @param array<string, mixed> $entity
     */
    private function generateEntityCode(array $entity, string $indent, string $builderVar): string
    {
        $name = $entity['name'];
        $prefab = $entity['prefab'] ?? null;
        $persistent = $entity['persistent'] ?? false;
        $tags = $entity['tags'] ?? [];
        $components = $entity['components'] ?? [];
        $children = $entity['children'] ?? [];

        $code = '';

        if ($prefab !== null) {
            $short = $this->shortName($prefab);
            $code .= "{$indent}{$builderVar}->prefab({$short}::class, " . var_export($name, true) . ")";
        } else {
            $code .= "{$indent}{$builderVar}->entity(" . var_export($name, true) . ")";
        }

        foreach ($components as $component) {
            $componentCode = $this->generateComponentConstructor($component);
            $code .= "\n{$indent}    ->with({$componentCode})";
        }

        if ($persistent) {
            $code .= "\n{$indent}    ->persist()";
        }

        foreach ($tags as $tag) {
            $code .= "\n{$indent}    ->tag(" . var_export($tag, true) . ")";
        }

        // Children as chained ->child() calls
        foreach ($children as $child) {
            $code .= "\n" . $this->generateChildCode($child, $indent . '    ');
        }

        $code .= ";\n";
        return $code;
    }

    /**
     * @param array<string, mixed> $child
     */
    private function generateChildCode(array $child, string $indent): string
    {
        $name = $child['name'];
        $components = $child['components'] ?? [];
        $children = $child['children'] ?? [];

        $code = "{$indent}->child(" . var_export($name, true) . ")";

        foreach ($components as $component) {
            $componentCode = $this->generateComponentConstructor($component);
            $code .= "\n{$indent}    ->with({$componentCode})";
        }

        foreach ($children as $grandchild) {
            $code .= "\n" . $this->generateChildCode($grandchild, $indent . '    ');
        }

        return $code;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function generateComponentConstructor(array $data): string
    {
        $class = $data['_class'] ?? null;
        if ($class === null) {
            throw new RuntimeException('Component data missing _class field');
        }

        $short = $this->shortName($class);
        $args = $this->generateConstructorArgs($class, $data);

        if (empty($args)) {
            return "new {$short}()";
        }

        return "new {$short}({$args})";
    }

    /**
     * @param array<string, mixed> $data
     */
    private function generateConstructorArgs(string $className, array $data): string
    {
        if (!class_exists($className)) {
            return '';
        }

        $ref = new ReflectionClass($className);
        $constructor = $ref->getConstructor();
        if ($constructor === null) {
            return '';
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            if (!array_key_exists($name, $data)) {
                continue;
            }

            $value = $data[$name];
            $type = $param->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

            $rendered = $this->renderValue($value, $typeName);
            if ($rendered !== null) {
                $args[] = "{$name}: {$rendered}";
            }
        }

        return implode(', ', $args);
    }

    private function renderValue(mixed $value, ?string $typeName): ?string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return (string)$value;
        }

        if (is_float($value)) {
            return $this->renderFloat($value);
        }

        if (is_string($value)) {
            return var_export($value, true);
        }

        if (is_array($value)) {
            return match ($typeName) {
                Vec2::class => $this->renderVec2($value),
                Vec3::class => $this->renderVec3($value),
                Color::class => $this->renderColor($value),
                default => $this->renderArray($value),
            };
        }

        return null;
    }

    /** @param array<string, mixed> $value */
    private function renderVec2(array $value): string
    {
        $x = $this->renderFloat((float)($value['x'] ?? $value[0] ?? 0));
        $y = $this->renderFloat((float)($value['y'] ?? $value[1] ?? 0));
        return "new Vec2({$x}, {$y})";
    }

    /** @param array<string, mixed> $value */
    private function renderVec3(array $value): string
    {
        $x = $this->renderFloat((float)($value['x'] ?? $value[0] ?? 0));
        $y = $this->renderFloat((float)($value['y'] ?? $value[1] ?? 0));
        $z = $this->renderFloat((float)($value['z'] ?? $value[2] ?? 0));
        return "new Vec3({$x}, {$y}, {$z})";
    }

    /** @param array<string, mixed> $value */
    private function renderColor(array $value): string
    {
        $r = $this->renderFloat((float)($value['r'] ?? 1));
        $g = $this->renderFloat((float)($value['g'] ?? 1));
        $b = $this->renderFloat((float)($value['b'] ?? 1));
        $a = $this->renderFloat((float)($value['a'] ?? 1));
        return "new Color({$r}, {$g}, {$b}, {$a})";
    }

    /** @param array<string, mixed> $value */
    private function renderArray(array $value): string
    {
        $items = [];
        foreach ($value as $k => $v) {
            $rendered = $this->renderValue($v, null);
            if ($rendered !== null) {
                if (is_string($k)) {
                    $items[] = var_export($k, true) . " => {$rendered}";
                } else {
                    $items[] = $rendered;
                }
            }
        }
        return '[' . implode(', ', $items) . ']';
    }

    private function renderFloat(float $value): string
    {
        $str = (string)$value;
        if (!str_contains($str, '.') && !str_contains($str, 'E')) {
            $str .= '.0';
        }
        return $str;
    }

    /**
     * @param array<string, mixed>[] $entities
     * @param string[] $systems
     * @param array<string, mixed>|null $config
     * @return list<string>
     */
    private function collectUseStatements(array $entities, array $systems, ?array $config): array
    {
        $classes = [
            'PHPolygon\\Scene\\Scene',
            'PHPolygon\\Scene\\SceneBuilder',
        ];

        foreach ($systems as $system) {
            $classes[] = $system;
        }

        $this->collectEntityClasses($entities, $classes);

        if ($config !== null) {
            $classes[] = 'PHPolygon\\Scene\\SceneConfig';
        }

        // Deduplicate and sort
        $classes = array_unique($classes);
        sort($classes);

        return $classes;
    }

    /**
     * @param array<string, mixed>[] $entities
     * @param list<string> $classes
     */
    private function collectEntityClasses(array $entities, array &$classes): void
    {
        foreach ($entities as $entity) {
            if (isset($entity['prefab'])) {
                $classes[] = $entity['prefab'];
            }
            foreach ($entity['components'] ?? [] as $component) {
                if (isset($component['_class'])) {
                    $classes[] = $component['_class'];
                    $this->collectValueTypeClasses($component['_class'], $component, $classes);
                }
            }
            if (isset($entity['children'])) {
                $this->collectEntityClasses($entity['children'], $classes);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $classes
     */
    private function collectValueTypeClasses(string $componentClass, array $data, array &$classes): void
    {
        if (!class_exists($componentClass)) {
            return;
        }

        $ref = new ReflectionClass($componentClass);
        $constructor = $ref->getConstructor();
        if ($constructor === null) {
            return;
        }

        foreach ($constructor->getParameters() as $param) {
            if (!array_key_exists($param->getName(), $data)) {
                continue;
            }

            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType) {
                continue;
            }

            $typeName = $type->getName();
            if (in_array($typeName, [Vec2::class, Vec3::class, Color::class], true)) {
                $classes[] = $typeName;
            }
        }
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }

    private function extractNamespace(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');
        return $pos !== false ? substr($fqcn, 0, $pos) : '';
    }

    private function nameToClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function generateConfigMethod(array $config): ?string
    {
        // Filter out _class key
        $fields = array_filter($config, fn($k) => $k !== '_class', ARRAY_FILTER_USE_KEY);
        if (empty($fields)) {
            return null;
        }

        $code = "    public function getConfig(): SceneConfig\n";
        $code .= "    {\n";
        $code .= "        return new SceneConfig(\n";

        $args = [];
        foreach ($fields as $key => $value) {
            $rendered = $this->renderValue($value, null);
            if ($rendered !== null) {
                $args[] = "            {$key}: {$rendered}";
            }
        }

        $code .= implode(",\n", $args) . ",\n";
        $code .= "        );\n";
        $code .= "    }\n";

        return $code;
    }
}
