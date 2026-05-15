<?php

namespace TomvdPeet\BreadcrumbBundle\Loader;

use Symfony\Component\Routing\Attribute\Route;

class AttributeRouteNameResolver
{
    public function resolve(\ReflectionClass $class, \ReflectionMethod $method): ?string
    {
        $namePrefix = $this->resolveClassRouteNamePrefix($class);

        foreach ($this->getRouteAttributes($method) as $route) {
            if (null === $route->name) {
                continue;
            }

            return $namePrefix.$route->name;
        }

        return null;
    }

    private function resolveClassRouteNamePrefix(\ReflectionClass $class): string
    {
        $attribute = $class->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

        if (null === $attribute) {
            return '';
        }

        return $attribute->newInstance()->name ?? '';
    }

    /**
     * @return iterable<Route>
     */
    private function getRouteAttributes(\ReflectionMethod $method): iterable
    {
        foreach ($method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            yield $attribute->newInstance();
        }
    }
}
