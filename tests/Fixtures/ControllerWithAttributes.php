<?php

namespace TomvdPeet\BreadcrumbBundle\Tests\Fixtures;

use TomvdPeet\BreadcrumbBundle\Attribute\Breadcrumb;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Breadcrumb(title: 'first-breadcrumb')]
#[Breadcrumb(title: 'second-breadcrumb')]
class ControllerWithAttributes extends AbstractController
{
    #[Breadcrumb(title: 'third-breadcrumb')]
    public function indexAction(): array
    {
        return [];
    }
}
