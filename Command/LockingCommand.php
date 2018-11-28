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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

abstract class LockingCommand extends ContainerAwareCommand
{
    private $lock;

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

        $lockFactory = new LockFactory(new SemaphoreStore);
        $lockName = $this->getName() . $this->getLockArgs($input);
        $this->lock = $lockFactory->createLock($lockName);

        $blocking = $input->getOption('lock-block');

        if ($this->lock->acquire()) {
            return;
        }

        if (!$blocking) {
            throw new \RuntimeException('Unable to obtain lock for running command');
        }

        $output->writeln('<info>Another command is already running. Waiting for the lock.</info>');
        $this->lock->acquire(true);
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
}
