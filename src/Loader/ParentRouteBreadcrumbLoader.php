<?php

namespace TomvdPeet\BreadcrumbBundle\Loader;

use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;

final class ParentRouteBreadcrumbLoader implements BreadcrumbLoaderInterface
{
    public function __construct(
        private readonly AttributeBreadcrumbLoader $attributeLoader,
        private readonly ParentRouteControllerResolver $controllerResolver
    )
    {
    }

    /**
     * @return list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    public function load(BreadcrumbContext $context): iterable
    {
        foreach ($this->loadRouteChain($context, []) as $definition) {
            yield $definition;
        }
    }

    /**
     * @param list<string> $routeChain
     *
     * @return iterable<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    private function loadRouteChain(BreadcrumbContext $context, array $routeChain): array
    {
        $routeName = $context->routeName;

        if (null !== $routeName && \in_array($routeName, $routeChain, true)) {
            throw new \RuntimeException(sprintf(
                'Circular breadcrumb parent route detected: %s.',
                implode(' -> ', [...$routeChain, $routeName])
            ));
        }

        $methodDefinitions = array_values(iterator_to_array($this->attributeLoader->loadMethod($context), false));
        [$definitions, $parentRoute] = $this->resolveRouteDefinitions($methodDefinitions, $routeName);

        if (null !== $parentRoute) {
            return [
                ...$this->loadRouteChain(
                    $this->controllerResolver->resolve($parentRoute, $context->request),
                    null === $routeName ? $routeChain : [...$routeChain, $routeName]
                ),
                ...$definitions,
            ];
        }

        return [
            ...array_values(iterator_to_array($this->attributeLoader->loadClass($context), false)),
            ...$definitions,
        ];
    }

    /**
     * @param list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition> $definitions
     *
     * @return array{0: list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>, 1: ?string}
     */
    private function resolveRouteDefinitions(array $definitions, ?string $routeName): array
    {
        foreach ($definitions as $index => $definition) {
            if (!$definition instanceof BreadcrumbDefinition || null === $definition->parentRoute) {
                continue;
            }

            if (0 !== $index) {
                throw new \RuntimeException(sprintf(
                    'Breadcrumb route "%s" defines breadcrumb attributes before its parentRoute boundary.',
                    $routeName ?? '<unknown>'
                ));
            }

            foreach (array_slice($definitions, $index + 1) as $laterDefinition) {
                if ($laterDefinition instanceof BreadcrumbDefinition && null !== $laterDefinition->parentRoute) {
                    throw new \RuntimeException(sprintf(
                        'Breadcrumb route "%s" defines multiple parentRoute boundaries.',
                        $routeName ?? '<unknown>'
                    ));
                }
            }

            return [array_slice($definitions, $index), $definition->parentRoute];
        }

        return [$definitions, null];
    }
}
