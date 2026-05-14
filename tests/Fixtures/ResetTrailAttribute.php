<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Fixtures;

use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;
use TomvdPeet\BreadcrumbBundle\Attribute\ResetBreadcrumbTrail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Breadcrumb(title: 'first-breadcrumb')]
class ResetTrailAttribute extends AbstractController
{
    #[ResetBreadcrumbTrail]
    #[Breadcrumb(title: 'first-breadcrumb-again')]
    public function indexAction(): array
    {
        return [];
    }
}
