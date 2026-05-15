<?php

namespace TomvdPeet\BreadcrumbBundle\Compiler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;
use TomvdPeet\BreadcrumbBundle\Loader\AttributeBreadcrumbLoader;
use TomvdPeet\BreadcrumbBundle\Context\BreadcrumbContext;
use TomvdPeet\BreadcrumbBundle\Definition\ParentRouteDefinitionExpander;
use TomvdPeet\BreadcrumbBundle\Resolver\RouteControllerResolver;

final class CompiledBreadcrumbMetadataCompiler
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly AttributeBreadcrumbLoader $attributeLoader,
        private readonly ParentRouteDefinitionExpander $definitionExpander,
        private readonly RouteControllerResolver $controllerResolver
    )
    {
    }

    /**
     * @return array<string,list<array<string,mixed>>>
     */
    public function compile(): array
    {
        $routes = [];

        foreach ($this->router->getRouteCollection()->all() as $routeName => $route) {
            $controller = $this->controllerResolver->resolve($route->getDefault('_controller'));

            if (null === $controller) {
                continue;
            }

            $context = new BreadcrumbContext(new Request([], [], ['_route' => $routeName]), $controller, $routeName);
            $routes[$routeName] = [
                'classDefinitions' => array_values(iterator_to_array($this->attributeLoader->loadClass($context), false)),
                'methodDefinitions' => array_values(iterator_to_array($this->attributeLoader->loadMethod($context), false)),
            ];
        }

        $compiled = [];

        foreach (array_keys($routes) as $routeName) {
            $compiled[$routeName] = array_map(
                [$this, 'serializeDefinition'],
                $this->definitionExpander->expand(
                    $routeName,
                    $routes[$routeName]['classDefinitions'],
                    $routes[$routeName]['methodDefinitions'],
                    static function (string $parentRoute) use ($routes): array {
                        if (!isset($routes[$parentRoute])) {
                            throw new \RuntimeException(sprintf('Parent breadcrumb route "%s" could not be found.', $parentRoute));
                        }

                        return [
                            'routeName' => $parentRoute,
                            'classDefinitions' => $routes[$parentRoute]['classDefinitions'],
                            'methodDefinitions' => $routes[$parentRoute]['methodDefinitions'],
                        ];
                    }
                )
            );
        }

        return array_filter($compiled);
    }

    /**
     * @return array<string,mixed>
     */
    private function serializeDefinition(BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition $definition): array
    {
        if ($definition instanceof ResetTrailDefinition) {
            return ['type' => 'reset'];
        }

        if ($definition instanceof TemplateDefinition) {
            return [
                'type' => 'template',
                'template' => $definition->template,
            ];
        }

        return [
            'type' => 'breadcrumb',
            'title' => $definition->title,
            'routeName' => $definition->routeName,
            'routeParameters' => $definition->routeParameters,
            'routeAbsolute' => $definition->routeAbsolute,
            'position' => $definition->position,
            'attributes' => $definition->attributes,
        ];
    }
}
