<?php

/**
 * This file is part of the InfiniteCommonBundle.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

class ResilientManager implements ResilientManagerInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var \Raven_Client
     */
    private $raven;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param \Raven_Client $raven
     */
    public function __construct(ManagerRegistry $managerRegistry, \Raven_Client $raven = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->raven = $raven;
    }

    /**
     * {@inheritdoc}
     */
    public function getManager($name = null)
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

    /**
     * {@inheritdoc}
     */
    public function wrapCallable(callable $callback, callable $onFailure = null, $managerName = null)
    {
        $em = $this->getManager($managerName);

        try {
            $em->beginTransaction();
            $callback($em);
            $em->flush();
            $em->commit();
        } catch (ORMException $e) {
            $em->rollback();

            if ($onFailure && $onFailure($e)) {
                throw $e;
            }

            if ($this->raven) {
                $this->raven->captureException($e);
            }
        } catch (\Exception $e) {
            $em->rollback();

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function wrapPersist($object, callable $onFailure = null, $managerName = null)
    {
        $wrappedOnFailure = function ($e) use ($object, $onFailure) {
            return $onFailure($e, $object);
        };

        $this->wrapCallable(function (EntityManagerInterface $em) use ($object) {
            $em->persist($object);
        }, $wrappedOnFailure, $managerName);
    }
}
