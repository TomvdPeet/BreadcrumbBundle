<?php

namespace TomvdPeet\BreadcrumbBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use TomvdPeet\BreadcrumbBundle\CacheWarmer\CompiledBreadcrumbCacheWarmer;
use TomvdPeet\BreadcrumbBundle\Loader\BreadcrumbLoaderInterface;
use TomvdPeet\BreadcrumbBundle\Loader\CompiledBreadcrumbLoader;
use TomvdPeet\BreadcrumbBundle\Loader\ParentRouteBreadcrumbLoader;

class TomvdPeetBreadcrumbExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('tomvd_peet_breadcrumb.template', $config['template']);
        $container->setParameter('tomvd_peet_breadcrumb.cache_file', '%kernel.cache_dir%/tomvd_peet_breadcrumb/compiled.php');

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $debug = $container->hasParameter('kernel.debug') ? $container->getParameter('kernel.debug') : true;

        if ($debug) {
            $container->setAlias(BreadcrumbLoaderInterface::class, ParentRouteBreadcrumbLoader::class);
            $container->removeDefinition(CompiledBreadcrumbCacheWarmer::class);

            return;
        }

        $container
            ->getDefinition(CompiledBreadcrumbLoader::class)
            ->setArguments([
                '%tomvd_peet_breadcrumb.cache_file%',
                new Reference(ParentRouteBreadcrumbLoader::class),
            ]);

        $container->setAlias(BreadcrumbLoaderInterface::class, CompiledBreadcrumbLoader::class);
    }
}
