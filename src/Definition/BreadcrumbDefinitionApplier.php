<?php

namespace TomvdPeet\BreadcrumbBundle\Definition;

use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;

final class BreadcrumbDefinitionApplier
{
    public function apply(BreadcrumbDefinition|ResetTrailDefinition|TemplateDefinition $definition, Trail $trail): void
    {
        if ($definition instanceof ResetTrailDefinition) {
            $trail->reset();

            return;
        }

        if ($definition instanceof TemplateDefinition) {
            $trail->setTemplate($definition->template);

            return;
        }

        $trail->add(
            $definition->title,
            $definition->routeName,
            $definition->routeParameters,
            $definition->routeAbsolute,
            $definition->position,
            $definition->attributes
        );
    }
}
