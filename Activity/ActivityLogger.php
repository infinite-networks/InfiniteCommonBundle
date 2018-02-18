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
        $context = null,
        $swallowException = null,
        $logSuccess = null
    ) {
        if (!$context instanceof Context) {
            $context = new Context($context ?: []);
        }

        $this->callDepth++;
        $result = null;

        try {
            $result = $callable($context);
            $context->setResult($result);
        } catch (\PHPUnit_Exception $e) {
            throw $e;
        } catch (\Mockery\Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof AddContextExceptionInterface) {
                $e->addToContext($context);
            }

            return $this->handleException($e, $description, $context, $swallowException);
        } finally {
            $this->callDepth--;
        }

        $logSuccess = $this->resolve($logSuccess, $context);
        if ($logSuccess || (null === $logSuccess && $this->callDepth === 0)) {
            $this->logger->log(
                is_int($logSuccess) ? $logSuccess : $this->successLevel,
                $description,
                $context->toArray()
            );
        }

        return $result;
    }

    /**
     * Handles an exception.
     *
     * @param \Exception $e
     * @param string $description
     * @param Context $context
     * @param callable|bool $swallowException
     * @return null
     * @throws \Exception
     */
    private function handleException(\Exception $e, $description, Context $context, $swallowException)
    {
        $wrapped = $this->wrapException($e, $description, $context);
        $swallow = $this->resolve($swallowException, $e, $wrapped);

        if ($swallow || $this->callDepth === 1) {
            $this->logger->log($this->exceptionLevel, $wrapped->getMessage(), $wrapped->getContext()->toArray());
        }

        if ($swallow) {
            $this->raven->captureException($wrapped, ['extra' => $wrapped->getContext()->toArray()]);

            return null;
        }

        throw $wrapped;
    }

    /**
     * Resolves a variable that can be a callable or boolean.
     *
     * @param callable|bool $variable
     * @return bool
     */
    private function resolve($variable)
    {
        if (is_callable($variable)) {
            $args = func_get_args();
            return call_user_func_array($variable, array_splice($args, 1));
        }

        return $variable;
    }

    /**
     * Wraps an exception around a FailedActivityException. If the exception to be wrapped is a
     * FailedActivityException, combine the additionalContext.
     *
     * @param \Exception $e
     * @param string $description
     * @param Context $context
     * @return FailedActivityException
     */
    private function wrapException(\Exception $e, $description, Context $context)
    {
        if ($e instanceof FailedActivityException) {
            $context->merge($e->getContext());
        }

        return new FailedActivityException($e, $description, $context);
    }
}
