<?php
/*
 * This file is part of the BreadcrumbBundle.
 *
 * (c) Abhoryo <abhoryo@free.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
