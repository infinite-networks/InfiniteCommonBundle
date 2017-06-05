<?php

/**
 * This file is part of the Infinite Invocation project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

abstract class LockingCommand extends ContainerAwareCommand
{
    /**
     * @var LockHandler
     */
    private $lockHandler;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->addOption('lock-block', null, InputOption::VALUE_NONE, 'If the command is locked, block until a lock can be obtained.')
            ->addOption('skip-lock', null, InputOption::VALUE_NONE, 'Dont bother with a lock. Dragons.');
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if ($input->getOption('skip-lock')) {
            return;
        }

        $this->buildLockHandler($input);

        $blocking = $input->getOption('lock-block');

        if (!$this->lockHandler->lock()) {
            if (!$blocking) {
                throw new \RuntimeException('Unable to obtain lock for running command');
            }

            $output->writeln('<info>Another command is already running. Waiting for the lock.</info>');
        }

        if ($blocking && !$this->lockHandler->lock(true)) {
            throw new \RuntimeException('Unable to obtain lock');
        }
    }

    /**
     * Return a string that is appended to the lock name to allow different instances of the
     * command to have independent locks.
     *
     * @param InputInterface $input
     * @return string
     */
    protected function getLockArgs(InputInterface $input)
    {
        return '';
    }

    /**
     * Creates a lock handler.
     */
    private function buildLockHandler(InputInterface $input)
    {
        $lockPath = $this->getContainer()->getParameter('kernel.cache_dir').'/locks';

        $lockName = $this->getName();
        $lockNameArgs = $this->getLockArgs($input);

        $this->lockHandler = new LockHandler($lockName.$lockNameArgs, $lockPath);
    }
}
