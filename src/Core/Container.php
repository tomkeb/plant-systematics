<?php

declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Minimal reflection-based autowiring container. Class-typed constructor parameters are resolved
 * recursively; objects created outside (Config, Request) are registered with set().
 */
final class Container
{
    /** @var array<string, object> */
    private array $instances = [];

    public function set(object $instance): void
    {
        $this->instances[$instance::class] = $instance;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function get(string $class): object
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        $reflection = new ReflectionClass($class);
        $args = [];
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException("Cannot autowire parameter \${$parameter->getName()} of {$class}");
            }
        }

        return $this->instances[$class] = $reflection->newInstanceArgs($args);
    }
}
