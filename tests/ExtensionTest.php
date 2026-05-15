<?php

namespace TomvdPeet\BreadcrumbBundle\Tests;

use TomvdPeet\BreadcrumbBundle\BreadcrumbTrail\Trail;
use TomvdPeet\BreadcrumbBundle\CacheWarmer\CompiledBreadcrumbCacheWarmer;
use TomvdPeet\BreadcrumbBundle\DependencyInjection\TomvdPeetBreadcrumbExtension;
use TomvdPeet\BreadcrumbBundle\EventListener\BreadcrumbListener;
use TomvdPeet\BreadcrumbBundle\Loader\BreadcrumbLoaderInterface;
use TomvdPeet\BreadcrumbBundle\Loader\CompiledBreadcrumbLoader;
use TomvdPeet\BreadcrumbBundle\Loader\ParentRouteBreadcrumbLoader;
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

    public function testDebugContainerUsesRuntimeBreadcrumbLoader(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new TomvdPeetBreadcrumbExtension();
        $extension->load([], $container);

        self::assertSame(ParentRouteBreadcrumbLoader::class, (string) $container->getAlias(BreadcrumbLoaderInterface::class));
        self::assertFalse($container->hasDefinition(CompiledBreadcrumbCacheWarmer::class));
    }

    public function testProdContainerUsesCompiledBreadcrumbLoader(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $extension = new TomvdPeetBreadcrumbExtension();
        $extension->load([], $container);

        self::assertSame(CompiledBreadcrumbLoader::class, (string) $container->getAlias(BreadcrumbLoaderInterface::class));
        self::assertTrue($container->hasDefinition(CompiledBreadcrumbCacheWarmer::class));
    }
}
