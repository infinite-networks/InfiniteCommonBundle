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
     * The return value of this method is the return value of the supplied $callable unless an
     * exception is thrown, in that case null is returned.
     *
     * When no exception is thrown, the activity is considered successful and will be logged to the
     * logger configured in the ActivityLogger instance. Context provided to the logCallable method
     * and any additional context set by the callable will be added to the log call.
     *
     * The result of the callable will also be stored with the result key on the context if the
     * returned value is not null.
     *
     * In the case of an exception being thrown inside the callable, it will be caught and wrapped
     * with a FailedActivityException and logged to the configured logger with a higher level and
     * any context available.
     *
     * An optional parameter to logCallable, $swallowException, is a callable that can be used to
     * check if the exception that was caught should be silently swallowed and logged to Raven or
     * if it should be rethrown, returning true will swallow the exception.
     *
     * Finally, $logSuccess allows the caller of logCallable to indicate if a successful activity
     * should be logged. This parameter has no effect on a caught exception.
     *
     * @param string $description
     * @param callable $callable
     * @param Context|array $context
     * @param callable|bool $swallowException
     * @param callable|bool $logSuccess
     * @return mixed
     */
    public function logCallable(
        $description,
        callable $callable,
        $context = null,
        $swallowException = null,
        $logSuccess = null
    );
}
