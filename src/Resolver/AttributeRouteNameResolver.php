<?php

namespace TomvdPeet\BreadcrumbBundle\Resolver;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Route as RoutingRoute;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class AttributeRouteNameResolver
{
    public function __construct(
        private readonly ?RouterInterface $router = null,
        private readonly RouteControllerResolver $controllerResolver = new RouteControllerResolver()
    )
    {
    }

    public function resolve(\ReflectionClass $class, \ReflectionMethod $method, ?string $currentRouteName = null): ?string
    {
        $routeCollection = $this->router?->getRouteCollection();

        if (null !== $routeCollection) {
            $routeName = $this->resolveFromRouteCollection($routeCollection, $class, $method, $currentRouteName);

            if (null !== $routeName) {
                return $routeName;
            }
        }

        $namePrefix = $this->resolveClassRouteNamePrefix($class);

        foreach ($this->getRouteAttributes($method) as $route) {
            if (null === $route->name) {
                continue;
            }

            return $namePrefix.$route->name;
        }

        return null;
    }

    private function resolveFromRouteCollection(RouteCollection $routeCollection, \ReflectionClass $class, \ReflectionMethod $method, ?string $currentRouteName): ?string
    {
        if (null !== $currentRouteName) {
            $currentRoute = $routeCollection->get($currentRouteName);

            if (null !== $currentRoute && $this->routeMatchesMethod($currentRoute, $class, $method)) {
                return $currentRouteName;
            }
        }

        foreach ($routeCollection->all() as $routeName => $route) {
            if ($this->routeMatchesMethod($route, $class, $method)) {
                return $routeName;
            }
        }

        return null;
    }

    private function routeMatchesMethod(RoutingRoute $route, \ReflectionClass $class, \ReflectionMethod $method): bool
    {
        $controller = $this->controllerResolver->resolve($route->getDefault('_controller'));

        if (!\is_array($controller)) {
            return false;
        }

        return $controller[0] === $class->getName() && $controller[1] === $method->getName();
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
