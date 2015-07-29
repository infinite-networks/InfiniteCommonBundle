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

use Psr\Log\LoggerInterface;

class ActivityLogger implements ActivityLoggerInterface
{
    /**
     * The logger to log any activity messages to.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function logCallable($description, callable $callable)
    {
        $additionalContext = [];

        try {
            $result = $callable($additionalContext);
        } catch (\Exception $e) {
            $wrapped = new FailedActivityException($e, $description, $additionalContext);
            $this->logger->log(350, $wrapped->getMessage(), $additionalContext);

            throw $wrapped;
        }

        if (false === $result) {
            return;
        }

        if (is_array($result)) {
            $additionalContext = array_merge($additionalContext, $result);
        } else {
            $additionalContext['result'] = $result;
        }

        $this->logger->log(300, $description, $additionalContext);
    }
}
