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
     * If an output is specified, the generated log line is also output to the output.
     *
     * @var OutputInterface
     */
    private $output;

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
        $logSuccess = true
    ) {
        try {
            $result = $callable($context);
        } catch (\PHPUnit_Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->handleException($e, $description, $context, $swallowException);
        }

        $this->handleSuccess($logSuccess, $description, $context, $result);

        return $result;
    }

    /**
     * Sets an OutputInterface instance which will receive log entries in addition
     * to the configured LoggerInterface.
     *
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
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
        $this->log($this->exceptionLevel, $e->getMessage(), $e->getContext());

        if ($swallowException && $swallowException($e->getPrevious(), $e)) {
            $this->raven->captureException($e, ['extra' => $e->getContext()]);

            return null;
        }

        throw $e;
    }

    /**
     * Handles a success - only logging if $logSuccess is true.
     *
     * @param bool $logSuccess
     * @param $description
     * @param array $context
     * @param mixed $result
     */
    private function handleSuccess($logSuccess, $description, array $context, $result)
    {
        if (!$logSuccess) {
            return;
        }

        if (null !== $result) {
            $context['result'] = $result;
        }

        $this->log($this->successLevel, $description, $context);
    }

    /**
     * Logs to the Psr Logger and if an OutputInterface has been set, writes the same message
     * to the console.
     *
     * @param int $level
     * @param string $message
     * @param array $context
     */
    private function log($level, $message, array $context)
    {
        $this->logger->log($level, $message, $context);

        if ($this->output) {
            $wrap = $level > 300 ? 'error' : 'info';

            $this->output->writeln(sprintf('<%1$s>%2$s</%1$s>', $wrap, $message));
        }
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
