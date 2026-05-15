<?php

namespace TomvdPeet\BreadcrumbBundle\Loader;

use Symfony\Component\HttpFoundation\Request;

final class BreadcrumbContext
{
    public readonly ?string $routeName;

    public function __construct(
        public readonly Request $request,
        public readonly mixed $controller,
        ?string $routeName = null
    )
    {
        $this->routeName = $routeName ?? $request->attributes->get('_route');
    }
}
