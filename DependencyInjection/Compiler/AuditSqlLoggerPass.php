<?php

/**
 * This file is part of the Infinite CommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This pass replaces the original doctrine.dbal.logger definition because we cant
 * reach into the DoctrineBundle configuration to just add another logger to a chain -
 * the chain is likely to not exist.
 *
 * A hack, but it works.
 */
class AuditSqlLoggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('infinite_common.audit_sql_logger')) {
            return;
        }

        $chainLogger = new Definition('%doctrine.dbal.logger.chain.class%');
        $chainLogger->setPublic(false);

        $original = $container->getDefinition('doctrine.dbal.logger');
        $chainLogger->addMethodCall('addLogger', array($original));
        $chainLogger->addMethodCall('addLogger', array(new Reference('invocation_common.audit_sql_logger')));

        $loggerId = 'invocation.dbal.logger.chain';
        $container->setDefinition($loggerId, $chainLogger);

        $container->removeDefinition('doctrine.dbal.logger');
        $container->setAlias('doctrine.dbal.logger', $loggerId);
    }
}
