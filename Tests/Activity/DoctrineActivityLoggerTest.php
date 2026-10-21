<?php

/**
 * This file is part of the InfiniteCommonBundle.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Tests\Activity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Infinite\CommonBundle\Activity\ActivityLogger;
use Infinite\CommonBundle\Activity\ActivityLoggerInterface;
use Infinite\CommonBundle\Activity\DoctrineActivityLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

class DoctrineActivityLoggerTest extends TestCase
{
    /**
     * @var DoctrineActivityLogger
     */
    private $logger;

    /**
     * @var EntityManagerInterface|MockObject
     */
    private $open;

    /**
     * @var LoggerInterface|MockObject
     */
    private $psrLogger;

    /**
     * @var ManagerRegistry|MockObject
     */
    public $managerRegistry;

    /**
     * @var HubInterface|MockObject
     */
    private $sentry;

    /**
     * @var ActivityLoggerInterface|MockObject
     */
    private $wrappedLogger;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->psrLogger = $this->createMock(LoggerInterface::class);
        $this->sentry = $this->createMock(HubInterface::class);
        $this->sentry->method('withScope')->willReturnCallback(static function (callable $callback) {
            return $callback(new Scope());
        });

        $this->open = $this->createMock(EntityManagerInterface::class);
        $this->open->expects($this->any())
            ->method('isOpen')
            ->willReturn(true);
        $this->managerRegistry->expects($this->any())
            ->method('getManager')
            ->with(null)
            ->willReturn($this->open);

        $this->wrappedLogger = new ActivityLogger($this->psrLogger, $this->sentry);
        $this->logger = new DoctrineActivityLogger($this->wrappedLogger, $this->managerRegistry);
    }

    public function testLogCallableSucceeds()
    {
        $this->open->expects($this->once())
            ->method('beginTransaction');
        $this->open->expects($this->never())
            ->method('flush');
        $this->open->expects($this->once())
            ->method('commit');
        $this->open->expects($this->never())
            ->method('rollback');

        $callable = function () { return 'hello'; };

        $result = $this->logger->logCallable('Testing Callable', $callable);

        $this->assertEquals('hello', $result);
    }

    public function testLogCallableRollbackOnFailure()
    {
        $this->expectException(\Infinite\CommonBundle\Activity\FailedActivityException::class);
        $this->open->expects($this->once())
            ->method('beginTransaction');
        $this->open->expects($this->never())
            ->method('flush');
        $this->open->expects($this->once())
            ->method('commit')
            ->willThrowException(new ORMException());
        $this->open->expects($this->once())
            ->method('rollback');

        $callable = function () { return 'hello'; };

        $this->logger->logCallable('Testing Callable', $callable);
    }
}
