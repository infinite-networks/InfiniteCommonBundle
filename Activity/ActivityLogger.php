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
use Symfony\Component\Console\Output\OutputInterface;

class ActivityLogger implements ActivityLoggerInterface
{
    /**
     * Tracks the ActivityLogger call depth, stopping log output if we're a few levels
     * deep.
     *
     * @var int
     */
    private $callDepth = 0;

    /**
     * The log level for an exceptional activity.
     *
     * @var int
     */
    private $exceptionLevel;

    /**
     * The logger to log any activity messages to.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The exception reporting service.
     *
     * @var \Raven_Client
     */
    private $raven;

    /**
     * The log level for a successful activity.
     *
     * @var int
     */
    private $successLevel;

    /**
     * @param LoggerInterface $logger
     * @param \Raven_Client $raven
     * @param int $successLevel
     * @param int $exceptionLevel
     */
    public function __construct(LoggerInterface $logger, \Raven_Client $raven, $successLevel = 300, $exceptionLevel = 400)
    {
        $this->exceptionLevel = $exceptionLevel;
        $this->logger = $logger;
        $this->raven = $raven;
        $this->successLevel = $successLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function logCallable(
        $description,
        callable $callable,
        array $context = [],
        callable $swallowException = null,
        $logSuccess = null
    ) {
        $this->callDepth++;
        $result = null;

        try {
            $result = $callable($context);
        } catch (\PHPUnit_Exception $e) {
            throw $e;
        } catch (\Mockery\Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->handleException($e, $description, $context, $swallowException);
        } finally {
            $this->callDepth--;
        }

        if (true === $logSuccess || (null === $logSuccess && $this->callDepth === 0)) {
            $this->logger->log($this->successLevel, $description, $context);
        }

        return $result;
    }

    /**
     * Handles an exception.
     *
     * @param \Exception $e
     * @param string $description
     * @param array $context
     * @param callable $swallowException
     * @return null
     * @throws \Exception
     */
    private function handleException(\Exception $e, $description, array $context, callable $swallowException = null)
    {
        $e = $this->wrapException($e, $description, $context);
        $swallow = $swallowException && $swallowException($e->getPrevious(), $e);

        if ($swallow || $this->callDepth === 1) {
            $this->logger->log($this->exceptionLevel, $wrapped->getMessage(), $wrapped->getContext());
        }

        if ($swallow) {
            $this->raven->captureException($e, ['extra' => $e->getContext()]);

            return null;
        }

        throw $e;
    }

    /**
     * Wraps an exception around a FailedActivityException. If the exception to be wrapped is a
     * FailedActivityException, combine the additionalContext.
     *
     * @param \Exception $e
     * @param string $description
     * @param array $context
     * @return FailedActivityException
     */
    private function wrapException(\Exception $e, $description, array $context)
    {
        if ($e instanceof FailedActivityException) {
            $context = array_merge($e->getContext(), $context);
        }

        return new FailedActivityException($e, $description, $context);
    }
}
