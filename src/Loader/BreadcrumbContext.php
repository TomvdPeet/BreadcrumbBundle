<?php

namespace TomvdPeet\BreadcrumbBundle\Loader;

use Symfony\Component\HttpFoundation\Request;

final class BreadcrumbContext
{
    public function __construct(
        public readonly Request $request,
        public readonly mixed $controller
    )
    {
    }
}
