<?php

namespace APY\BreadcrumbTrailBundle;

use APY\BreadcrumbTrailBundle\BreadcrumbTrail\Trail;
use APY\BreadcrumbTrailBundle\DependencyInjection\APYBreadcrumbTrailExtension;
use APY\BreadcrumbTrailBundle\EventListener\BreadcrumbListener;
use APY\BreadcrumbTrailBundle\Twig\BreadcrumbTrailExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtensionTest extends TestCase
{
    public function testContainerHasExtension(): void
    {
        $container = new ContainerBuilder();
        $extension = new APYBreadcrumbTrailExtension();
        $extension->load([], $container);

        self::assertTrue($container->hasDefinition(Trail::class));
        self::assertTrue($container->hasDefinition(BreadcrumbListener::class));
        self::assertTrue($container->hasDefinition(BreadcrumbTrailExtension::class));
    }
}
