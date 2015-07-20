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

use Doctrine\ORM\EntityManagerInterface;

interface ResilientManagerInterface
{
    /**
     * Returns a manager. If this method encounters a closed Manager, it will reset it and return a
     * new instance.
     *
     * @param string|null $name
     * @return EntityManagerInterface
     */
    public function getManager($name = null);

    /**
     * Wraps a callable in a transaction. Callback should take a single argument for the
     * EntityManager.
     *
     * @param callable $callback
     * @param callable $onFailure
     * @param string $managerName
     */
    public function wrapCallable(callable $callback, callable $onFailure = null, $managerName = null);

    /**
     * Wraps an object's persistence and flushing in a transaction.
     *
     * @param mixed $object
     * @param callable $onFailure
     * @param string $managerName
     */
    public function wrapPersist($object, callable $onFailure = null, $managerName = null);
}
