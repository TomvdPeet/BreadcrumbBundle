<?php

namespace TomvdPeet\BreadcrumbBundle\Loader;

use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;
use TomvdPeet\BreadcrumbBundle\Attribute\ResetBreadcrumbTrail;
use TomvdPeet\BreadcrumbBundle\Definition\BreadcrumbDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\ResetTrailDefinition;
use TomvdPeet\BreadcrumbBundle\Definition\TemplateDefinition;

final class AttributeBreadcrumbLoader implements BreadcrumbLoaderInterface
{
    private const SUPPORTED_ATTRIBUTES = [
        Breadcrumb::class,
        ResetBreadcrumbTrail::class,
    ];

    public function load(BreadcrumbContext $context): iterable
    {
        $controller = $context->controller;

        $reflectableClass = \is_array($controller) ? $controller[0] : $controller;
        $reflectableMethod = \is_array($controller) ? $controller[1] : '__invoke';

        $class = new \ReflectionClass($reflectableClass);

        foreach ($this->loadFromReflection($class) as $definition) {
            yield $definition;
        }

        $method = $class->getMethod($reflectableMethod);

        foreach ($this->loadFromReflection($method) as $definition) {
            yield $definition;
        }
    }

    /**
     * @return iterable<BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition>
     */
    private function loadFromReflection(\ReflectionClass|\ReflectionMethod $reflected): iterable
    {
        foreach ($this->getSupportedAttributes($reflected) as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();

            if ($attribute instanceof ResetBreadcrumbTrail) {
                yield new ResetTrailDefinition();

                continue;
            }

            if (null !== $attribute->getTemplate()) {
                yield new TemplateDefinition($attribute->getTemplate());
            }

            yield new BreadcrumbDefinition(
                $attribute->getTitle(),
                $attribute->getRouteName(),
                $attribute->getRouteParameters(),
                $attribute->getRouteAbsolute(),
                $attribute->getPosition(),
                $attribute->getAttributes()
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
