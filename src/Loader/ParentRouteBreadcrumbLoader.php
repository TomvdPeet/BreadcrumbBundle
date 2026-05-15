<?php

namespace TomvdPeet\BreadcrumbBundle\Loader;

use TomvdPeet\BreadcrumbBundle\Context\BreadcrumbContext;
use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ParentRouteDefinitionExpander;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;
use TomvdPeet\BreadcrumbBundle\Resolver\ParentRouteControllerResolver;

final class ParentRouteBreadcrumbLoader implements BreadcrumbLoaderInterface
{
    public function __construct(
        private readonly AttributeBreadcrumbLoader $attributeLoader,
        private readonly ParentRouteControllerResolver $controllerResolver,
        private readonly ParentRouteDefinitionExpander $definitionExpander
    )
    {
    }

    /**
     * @return list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    public function load(BreadcrumbContext $context): iterable
    {
        foreach ($this->definitionExpander->expand(
            $context->routeName,
            array_values(iterator_to_array($this->attributeLoader->loadClass($context), false)),
            array_values(iterator_to_array($this->attributeLoader->loadMethod($context), false)),
            fn (string $parentRoute): array => $this->loadParentRoute($parentRoute, $context)
        ) as $definition) {
            yield $definition;
        }
    }

    /**
     * @return array{routeName: string, classDefinitions: list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>, methodDefinitions: list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>}
     */
    private function loadParentRoute(string $parentRoute, BreadcrumbContext $context): array
    {
        $parentContext = $this->controllerResolver->resolve($parentRoute, $context->request);

        return [
            'routeName' => $parentRoute,
            'classDefinitions' => array_values(iterator_to_array($this->attributeLoader->loadClass($parentContext), false)),
            'methodDefinitions' => array_values(iterator_to_array($this->attributeLoader->loadMethod($parentContext), false)),
        ];
    }
}
