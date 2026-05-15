<?php

namespace TomvdPeet\BreadcrumbBundle\Loader;

use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;
use TomvdPeet\BreadcrumbBundle\Attribute\ResetBreadcrumbTrail;
use TomvdPeet\BreadcrumbBundle\Context\BreadcrumbContext;
use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;
use TomvdPeet\BreadcrumbBundle\Resolver\AttributeRouteNameResolver;

final class AttributeBreadcrumbLoader implements BreadcrumbLoaderInterface
{
    private const SUPPORTED_ATTRIBUTES = [
        Breadcrumb::class,
        ResetBreadcrumbTrail::class,
    ];

    public function __construct(
        private readonly AttributeRouteNameResolver $routeNameResolver = new AttributeRouteNameResolver()
    )
    {
    }

    public function load(BreadcrumbContext $context): iterable
    {
        [$class, $method] = $this->reflectController($context->controller);

        foreach ($this->loadFromReflection($class) as $definition) {
            yield $definition;
        }

        foreach ($this->loadFromReflection($method, $class) as $definition) {
            yield $definition;
        }
    }

    /**
     * @return iterable<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    public function loadClass(BreadcrumbContext $context): iterable
    {
        [$class] = $this->reflectController($context->controller);

        yield from $this->loadFromReflection($class);
    }

    /**
     * @return iterable<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    public function loadMethod(BreadcrumbContext $context): iterable
    {
        [$class, $method] = $this->reflectController($context->controller);

        yield from $this->loadFromReflection($method, $class);
    }

    /**
     * @return array{0: \ReflectionClass, 1: \ReflectionMethod}
     */
    private function reflectController(mixed $controller): array
    {
        $reflectableClass = \is_array($controller) ? $controller[0] : $controller;
        $reflectableMethod = \is_array($controller) ? $controller[1] : '__invoke';

        $class = new \ReflectionClass($reflectableClass);

        return [$class, $class->getMethod($reflectableMethod)];
    }

    /**
     * @return iterable<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    private function loadFromReflection(\ReflectionClass|\ReflectionMethod $reflected, ?\ReflectionClass $class = null): iterable
    {
        $resolvedRouteName = null;
        $routeNameResolved = false;

        foreach ($this->getSupportedAttributes($reflected) as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();

            if ($attribute instanceof ResetBreadcrumbTrail) {
                yield new ResetTrailDefinition();

                continue;
            }

            if (null !== $attribute->getTemplate()) {
                yield new TemplateDefinition($attribute->getTemplate());
            }

            if (null === $attribute->getRouteName() && $reflected instanceof \ReflectionMethod && null !== $class && false === $routeNameResolved) {
                $resolvedRouteName = $this->routeNameResolver->resolve($class, $reflected);
                $routeNameResolved = true;
            }

            yield new BreadcrumbDefinition(
                $attribute->getTitle(),
                $attribute->getRouteName() ?? $resolvedRouteName,
                $attribute->getRouteParameters(),
                $attribute->getRouteAbsolute(),
                $attribute->getPosition(),
                $attribute->getAttributes(),
                $attribute->getParentRoute()
            );
        }
    }

    /**
     * @return iterable<\ReflectionAttribute>
     */
    private function getSupportedAttributes(\ReflectionClass|\ReflectionMethod $reflected): iterable
    {
        foreach ($reflected->getAttributes() as $reflectionAttribute) {
            foreach (self::SUPPORTED_ATTRIBUTES as $attributeClass) {
                if (is_a($reflectionAttribute->getName(), $attributeClass, true)) {
                    yield $reflectionAttribute;

                    break;
                }
            }
        }
    }
}
