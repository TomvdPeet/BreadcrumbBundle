<?php

namespace TomvdPeet\BreadcrumbBundle\Resolver;

final class RouteControllerResolver
{
    /**
     * @return class-string|array{0: class-string, 1: string}|null
     */
    public function resolve(mixed $controller): string|array|null
    {
        if (\is_string($controller) && str_contains($controller, '::')) {
            [$class, $method] = explode('::', $controller, 2);

            if (class_exists($class)) {
                return [$class, $method];
            }
        }

        if (\is_string($controller) && class_exists($controller)) {
            return $controller;
        }

        return null;
    }
}
