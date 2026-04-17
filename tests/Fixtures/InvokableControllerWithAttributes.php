<?php

namespace APY\BreadcrumbTrailBundle\Fixtures;

use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;

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
