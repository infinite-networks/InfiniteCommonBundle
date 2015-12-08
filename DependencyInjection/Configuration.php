<?php

/**
 * This file is part of the Infinite CommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration definition for CommonBundle
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('infinite_common');

        $rootNode
            ->children()
                ->arrayNode('activity')
                    ->children()
                        ->scalarNode('doctrine')->defaultFalse()->end()
                        ->scalarNode('logger')->end()
                        ->scalarNode('success_level')->defaultValue(300)->end()
                        ->scalarNode('exception_level')->defaultValue(400)->end()
                    ->end()
                ->end()
                ->booleanNode('form')->defaultTrue()->end()
                ->arrayNode('menus')
                    ->example(array('navigation'))
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('log')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('raven_dsn')->defaultNull()->end()
                        ->scalarNode('request_channel')->defaultNull()->end()
                        ->scalarNode('sql_channel')->defaultNull()->end()
                    ->end()
                ->end()
                ->booleanNode('twig')->defaultTrue()->end()
            ->end();

        return $treeBuilder;
    }
}
