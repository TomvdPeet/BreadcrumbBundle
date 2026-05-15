<?php

namespace TomvdPeet\BreadcrumbBundle\Definition;


final class ParentRouteDefinitionExpander
{
    /**
     * @param list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition> $classDefinitions
     * @param list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition> $methodDefinitions
     * @param callable(string): array{routeName: string, classDefinitions: list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>, methodDefinitions: list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>} $parentRouteLoader
     *
     * @return list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    public function expand(?string $routeName, array $classDefinitions, array $methodDefinitions, callable $parentRouteLoader): array
    {
        return $this->expandRoute($routeName, $classDefinitions, $methodDefinitions, $parentRouteLoader, []);
    }

    /**
     * @param list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition> $classDefinitions
     * @param list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition> $methodDefinitions
     * @param callable(string): array{routeName: string, classDefinitions: list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>, methodDefinitions: list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>} $parentRouteLoader
     * @param list<string> $routeChain
     *
     * @return list<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    private function expandRoute(?string $routeName, array $classDefinitions, array $methodDefinitions, callable $parentRouteLoader, array $routeChain): array
    {
        if (null !== $routeName && \in_array($routeName, $routeChain, true)) {
            throw new \RuntimeException(sprintf(
                'Circular breadcrumb parent route detected: %s.',
                implode(' -> ', [...$routeChain, $routeName])
            ));
        }

        [$definitions, $parentRoute] = $this->resolveRouteDefinitions($methodDefinitions, $routeName);

        if (null !== $parentRoute) {
            $parentRouteDefinitionSet = $parentRouteLoader($parentRoute);

            return [
                ...$this->expandRoute(
                    $parentRouteDefinitionSet['routeName'],
                    $parentRouteDefinitionSet['classDefinitions'],
                    $parentRouteDefinitionSet['methodDefinitions'],
                    $parentRouteLoader,
                    null === $routeName ? $routeChain : [...$routeChain, $routeName]
                ),
                ...$definitions,
            ];
        }

        return [
            ...$classDefinitions,
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
