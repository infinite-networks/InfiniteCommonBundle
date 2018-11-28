<?php

/**
 * This file is part of the Infinite CommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleLoggingListener implements EventSubscriberInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function error(ConsoleErrorEvent $event)
    {
        $command = $event->getCommand();
        $exception = $event->getError();
        $input = $event->getInput();

        $message = sprintf(
            '%s: %s (uncaught exception) at %s line %s while running console command `%s`',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $command->getName()
        );

        $this->logger->error($message, array(
            'arguments' => $input->getArguments(),
            'options' => $input->getOptions()
        ));
    }

    public function terminate(ConsoleTerminateEvent $event)
    {
        $statusCode = $event->getExitCode();
        $command = $event->getCommand();
        $input = $event->getInput();

        if ($statusCode === 0) {
            return;
        }

        if ($statusCode > 255) {
            $statusCode = 255;
            $event->setExitCode($statusCode);
        }

        $this->logger->error(sprintf(
            'Command `%s` exited with status code %d',
            $command->getName(),
            $statusCode
        ), array(
            'arguments' => $input->getArguments(),
            'options' => $input->getOptions()
        ));
    }

    public static function getSubscribedEvents()
    {
        return array(
            ConsoleEvents::ERROR => 'error',
            ConsoleEvents::TERMINATE => 'terminate'
        );
    }
}
