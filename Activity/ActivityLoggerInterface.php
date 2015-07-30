<?php

/**
 * This file is part of the InfiniteCommonBundle.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Activity;

interface ActivityLoggerInterface
{
    /**
     * Logs the activity to appropriate places depending on if the activity is successful or not.
     *
     * Depending on the behaviour of the callable, the activity will be logged into different
     * locations:
     *
     *  * If the $activity callable returns false, no activity is logged
     *  * If the $activity callable returns true or nothing, the description is logged.
     *  * If the $activity callable returns an array or object, the description is
     *    logged with context being set to the array.
     *  * If an exception is thrown, we wrap the exception in another one that contains
     *    context from the callback.
     *
     * The callable is provided with a parameter for additionalContext, which if used by reference,
     * additional context information can be set during the callable to be provided for exception
     * reporting.
     *
     * If supplied, the swallowException callable will be called with the exception that was caught
     * and a wrapped FailedActivityException. Returning true from the callable will cause the exception
     * to be swallowed and logged to Raven with the additional context.
     *
     * If the callable raises a FailedActivityException itself, it will wrap the
     * FailedActivityException and combine context from deeper in the exception chain.
     *
     * @param string $description
     * @param callable $callable
     * @param array $context
     * @param callable $swallowException
     */
    public function logCallable($description, callable $callable, array $context = [], callable $swallowException = null);
}
