<?php

namespace TomvdPeet\BreadcrumbBundle\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use TomvdPeet\BreadcrumbBundle\Context\BreadcrumbContext;

final class ParentRouteControllerResolver
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly RouteControllerResolver $controllerResolver
    )
    {
    }

    public function resolve(string $routeName, Request $request): BreadcrumbContext
    {
        $route = $this->router->getRouteCollection()->get($routeName);

        if (null === $route) {
            throw new \RuntimeException(sprintf('Parent breadcrumb route "%s" could not be found.', $routeName));
        }

        $controller = $this->controllerResolver->resolve($route->getDefault('_controller'));

        if (null === $controller) {
            throw new \RuntimeException(sprintf(
                'Parent breadcrumb route "%s" does not reference a reflectable controller.',
                $routeName
            ));
        }

        return new BreadcrumbContext(
            $request,
            $controller,
            $routeName
        );
    }
}
