<?php

namespace TomvdPeet\BreadcrumbBundle\Loader;

use TomvdPeet\BreadcrumbBundle\Context\BreadcrumbContext;
use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;

final class CompiledBreadcrumbLoader implements BreadcrumbLoaderInterface
{
    /**
     * @var array<string,list<array<string,mixed>>>|null
     */
    private ?array $definitionsByRoute = null;

    public function __construct(
        private readonly string $cacheFile,
        private readonly ?BreadcrumbLoaderInterface $fallbackLoader = null
    )
    {
    }

    /**
     * @return iterable<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    public function load(BreadcrumbContext $context): iterable
    {
        $routeName = $context->routeName;
        $definitionsByRoute = $this->definitionsByRoute();

        if (null !== $routeName && isset($definitionsByRoute[$routeName])) {
            foreach ($definitionsByRoute[$routeName] as $definition) {
                yield $this->deserializeDefinition($definition);
            }

            return;
        }

        if (null !== $this->fallbackLoader) {
            yield from $this->fallbackLoader->load($context);
        }
    }

    /**
     * @return array<string,list<array<string,mixed>>>
     */
    private function definitionsByRoute(): array
    {
        if (null !== $this->definitionsByRoute) {
            return $this->definitionsByRoute;
        }

        if (!is_file($this->cacheFile)) {
            return $this->definitionsByRoute = [];
        }

        $definitionsByRoute = require $this->cacheFile;

        if (!\is_array($definitionsByRoute)) {
            throw new \RuntimeException(sprintf('Compiled breadcrumb cache "%s" did not return an array.', $this->cacheFile));
        }

        return $this->definitionsByRoute = $definitionsByRoute;
    }

    /**
     * @param array<string,mixed> $definition
     */
    private function deserializeDefinition(array $definition): BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition
    {
        return match ($definition['type'] ?? null) {
            'reset' => new ResetTrailDefinition(),
            'template' => new TemplateDefinition($definition['template']),
            'breadcrumb' => new BreadcrumbDefinition(
                $definition['title'],
                $definition['routeName'],
                $definition['routeParameters'],
                $definition['routeAbsolute'],
                $definition['position'],
                $definition['attributes']
            ),
            default => throw new \RuntimeException('Compiled breadcrumb cache contains an unknown definition type.'),
        };
    }
}
