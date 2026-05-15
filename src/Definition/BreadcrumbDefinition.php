<?php

namespace TomvdPeet\BreadcrumbBundle\Definition;

final class BreadcrumbDefinition
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $routeName = null,
        public readonly array $routeParameters = [],
        public readonly bool $routeAbsolute = true,
        public readonly int $position = 0,
        public readonly array $attributes = [],
        public readonly ?string $parentRoute = null
    )
    {
    }
}
