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

use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class InfiniteCommonExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        // Form factory wrapper
        if ($config['form']) {
            $loader->load('form.xml');
        }

        // We've got a menu so we need to load the menu configuration
        if ($config['menus']) {
            $loader->load('menu.xml');
        }

        foreach ($config['menus'] as $menu) {
            $definition = new DefinitionDecorator('infinite_common.menu_prototype');
            $definition->setFactoryService('infinite_common.menu.builder');
            $definition->setFactoryMethod('buildMenu');
            $definition->addArgument($menu);
            $definition->addTag('knp_menu.menu', array(
                'alias' => $menu,
            ));

            $container->setDefinition(sprintf('infinite_common.menu.%s', $menu), $definition);
        }

        // Twig Extensions
        if ($config['twig']) {
            $loader->load('twig.xml');
        }
    }
}
