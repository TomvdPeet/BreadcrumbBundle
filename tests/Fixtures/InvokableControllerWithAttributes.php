<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Fixtures;

use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;

#[Breadcrumb(title: 'first-breadcrumb')]
#[Breadcrumb(title: 'second-breadcrumb')]
class InvokableControllerWithAttributes
{
    #[Breadcrumb(title: 'third-breadcrumb')]
    public function __invoke(): array
    {
        return [];
    }
}
