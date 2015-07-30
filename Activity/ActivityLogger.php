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
     * @var \Raven_Client
     */
    private $raven;

    /**
     * @param LoggerInterface $logger
     * @param \Raven_Client $raven
     */
    public function __construct(LoggerInterface $logger, \Raven_Client $raven)
    {
        $this->logger = $logger;
        $this->raven = $raven;
    }

    /**
     * {@inheritdoc}
     */
    public function logCallable($description, callable $callable, array $context = [], callable $swallowException = null)
    {
        try {
            $result = $callable($context);
        } catch (\PHPUnit_Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            $e = $this->wrapException($e, $description, $context);
            $this->log(400, $e->getMessage(), $e->getContext());

            if ($swallowException && $swallowException($e->getPrevious(), $e)) {
                $this->raven->captureException($e, ['extra' => $e->getContext()]);

                return;
            }

            throw $e;
        }

        if (false === $result) {
            return;
        }

        if (is_array($result)) {
            $context = array_merge($context, $result);
        } elseif (null !== $result) {
            $context['result'] = $result;
        }

        $this->log(300, $description, $context);
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
