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
                ->booleanNode('form')->defaultTrue()->end()
                ->arrayNode('menus')
                    ->example(array('navigation'))
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('sql_logger')->defaultNull()->end()
                ->booleanNode('twig')->defaultTrue()->end()
            ->end();

        return $treeBuilder;
    }
}
