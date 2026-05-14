<?php

namespace TomvdPeet\BreadcrumbBundle\Tests;

use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use TomvdPeet\BreadcrumbBundle\DependencyInjection\TomvdPeetBreadcrumbExtension;
use TomvdPeet\BreadcrumbBundle\EventListener\BreadcrumbListener;
use TomvdPeet\BreadcrumbBundle\Twig\BreadcrumbTrailExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtensionTest extends TestCase
{
    public function testContainerHasExtension(): void
    {
        $container = new ContainerBuilder();
        $extension = new TomvdPeetBreadcrumbExtension();
        $extension->load([], $container);

        self::assertTrue($container->hasDefinition(Trail::class));
        self::assertTrue($container->hasDefinition(BreadcrumbListener::class));
        self::assertTrue($container->hasDefinition(BreadcrumbTrailExtension::class));
    }
}
