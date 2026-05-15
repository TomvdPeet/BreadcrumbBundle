<?php

namespace TomvdPeet\BreadcrumbBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('tomvd_peet_breadcrumb');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('template')
                    ->defaultValue('@TomvdPeetBreadcrumb/breadcrumbtrail.html.twig')
                ->end()
             ->end()
        ;

        return $treeBuilder;
    }
}
