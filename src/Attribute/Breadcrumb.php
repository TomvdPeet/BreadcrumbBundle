<?php

namespace TomvdPeet\BreadcrumbBundle\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Breadcrumb
{
    public function __construct(
        public string $title,
        public ?string $routeName = null,
        public array $routeParameters = [],
        public bool $routeAbsolute = true,
        public int $position = 0,
        public ?string $template = null,
        public array $attributes = [],
        public ?string $parentRoute = null
    )
    {
    }
}
