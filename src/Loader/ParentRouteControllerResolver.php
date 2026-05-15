<?php

namespace TomvdPeet\BreadcrumbBundle\Loader;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class ParentRouteControllerResolver
{
    public function __construct(
        private readonly RouterInterface $router
    )
    {
    }

    public function resolve(string $routeName, Request $request): BreadcrumbContext
    {
        $route = $this->router->getRouteCollection()->get($routeName);

        if (null === $route) {
            throw new \RuntimeException(sprintf('Parent breadcrumb route "%s" could not be found.', $routeName));
        }

        return new BreadcrumbContext(
            $request,
            $this->resolveController($route->getDefault('_controller'), $routeName),
            $routeName
        );
    }

    /**
     * @return class-string|array{0: class-string, 1: string}
     */
    private function resolveController(mixed $controller, string $routeName): string|array
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

        throw new \RuntimeException(sprintf(
            'Parent breadcrumb route "%s" does not reference a reflectable controller.',
            $routeName
        ));
    }
}
