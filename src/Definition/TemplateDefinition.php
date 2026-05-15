<?php

namespace TomvdPeet\BreadcrumbBundle\Definition;

final class TemplateDefinition
{
    public function __construct(
        public readonly string $template
    )
    {
    }
}
