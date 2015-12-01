<?php

/**
 * This file is part of the InfiniteCommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Activity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineActivityLogger implements ActivityLoggerInterface
{
    /**
     * @var ActivityLoggerInterface
     */
    private $activityLogger;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ActivityLoggerInterface $activityLogger
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ActivityLoggerInterface $activityLogger, ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        $this->activityLogger = $activityLogger;
    }

    public function logCallable(
        $description,
        callable $callable,
        $context = null,
        $swallowException = null,
        $logSuccess = null,
        $managerName = null
    ) {
        $wrappedCallable = $this->wrapCallable($callable, $managerName);

        return $this->activityLogger->logCallable($description, $wrappedCallable, $context, $swallowException, $logSuccess);
    }

    /**
     * @param $name
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    private function getManager($name)
    {
        $manager = $this->managerRegistry->getManager($name);
        if (!$manager->isOpen()) {
            $this->managerRegistry->resetManager($name);

            $manager = $this->managerRegistry->getManager($name);
        }

        if (!$manager->isOpen()) {
            throw new \RuntimeException('Tried to get open manager, failed.');
        }

        return $manager;
    }

    private function wrapCallable(callable $callable, $managerName)
    {
        return function (Context $context) use ($callable, $managerName) {
            $manager = $this->getManager($managerName);
            $transactions = $manager instanceof EntityManagerInterface;
            $return = null;

            try {
                $transactions && $manager->beginTransaction();
                $return = $callable($context, $manager);
                $transactions && $manager->commit();
            } catch (\Exception $e) {
                $transactions && $manager->rollback();

                throw $e;
            }

            return $return;
        };
    }
}
