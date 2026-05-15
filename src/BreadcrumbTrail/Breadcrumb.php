<?php

namespace TomvdPeet\BreadcrumbBundle\BreadcrumbTrail;

class Breadcrumb
{
    public function __construct(
        public string $title,
        public ?string $url = null,
        public mixed $attributes = []
    )
    {
    }
}
