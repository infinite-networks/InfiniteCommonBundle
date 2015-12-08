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
        $loader->load('console_logging.xml');
        $loader->load('doctrine.xml');

        if (isset($config['activity'])) {
            $loader->load('activity.xml');

            if ($config['activity']['doctrine']) {
                $loader->load('activity_doctrine.xml');

                $container->setAlias('infinite_common.activity_logger', 'infinite_common.activity_logger.doctrine');
            } else {
                $container->setAlias('infinite_common.activity_logger', 'infinite_common.activity_logger.inner');
            }

            $container->setAlias('infinite_common.activity_logger.logger', $config['activity']['logger']);
            $container->setParameter('infinite_common.activity_logger.success_level', $config['activity']['success_level']);
            $container->setParameter('infinite_common.activity_logger.exception_level', $config['activity']['exception_level']);
        }

        // Form factory wrapper
        if ($config['form']) {
            $loader->load('form.xml');
        }

        // We've got a menu so we need to load the menu configuration
        if ($config['menus']) {
            $loader->load('menu.xml');
        }

        // Loads the Raven client for error reporting to a Sentry server
        if (null !== $config['log']['raven_dsn']) {
            $loader->load('raven.xml');

            $container->setParameter('infinite_common.raven_dsn', $config['log']['raven_dsn']);
        }

        // Loads the Raven client for error reporting to a Sentry server
        if ($config['log']['request_channel']) {
            $loader->load('request_logger.xml');

            $container->getDefinition('infinite_common.logger.request')->addTag('monolog.logger', array(
                'channel' => $config['log']['request_channel']
            ));
        }

        // Audit SQL Logger that audits non SELECT sql queries to the specified
        // logger channel in $config['sql_logger']
        if ($config['log']['sql_channel']) {
            $loader->load('sql_logger.xml');

            $container->getDefinition('infinite_common.logger.audit_sql')->addTag('monolog.logger', array(
                'channel' => $config['log']['sql_channel']
            ));
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
