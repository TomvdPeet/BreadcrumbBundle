<?php

namespace TomvdPeet\BreadcrumbBundle\Resolver;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Route as RoutingRoute;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use TomvdPeet\BreadcrumbBundle\Exception\AmbiguousBreadcrumbRouteNameException;

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
            $routeName = $this->resolveFromRouteCollection($routeCollection, $class, $method);

            if (null !== $routeName) {
                return $routeName;
            }
        }

        $routeNames = $this->getNamedRouteAttributeNames($class, $method);

        if (1 < \count($routeNames)) {
            throw AmbiguousBreadcrumbRouteNameException::forControllerMethod($this->formatControllerMethod($class, $method), $routeNames);
        }

        return $routeNames[0] ?? null;
    }

    private function resolveFromRouteCollection(RouteCollection $routeCollection, \ReflectionClass $class, \ReflectionMethod $method): ?string
    {
        $routeNames = [];

        foreach ($routeCollection->all() as $routeName => $route) {
            if ($this->routeMatchesMethod($route, $class, $method)) {
                $routeNames[] = $routeName;
            }
        }

        if (1 < \count($routeNames)) {
            throw AmbiguousBreadcrumbRouteNameException::forControllerMethod($this->formatControllerMethod($class, $method), $routeNames);
        }

        return $routeNames[0] ?? null;
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
     * @return list<string>
     */
    private function getNamedRouteAttributeNames(\ReflectionClass $class, \ReflectionMethod $method): array
    {
        $routeNames = [];
        $namePrefix = $this->resolveClassRouteNamePrefix($class);

        foreach ($method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $route = $attribute->newInstance();

            if (null === $route->name) {
                continue;
            }

            $routeNames[] = $namePrefix.$route->name;
        }

        return $routeNames;
    }

    private function formatControllerMethod(\ReflectionClass $class, \ReflectionMethod $method): string
    {
        return $class->getName().'::'.$method->getName();
    }
}
