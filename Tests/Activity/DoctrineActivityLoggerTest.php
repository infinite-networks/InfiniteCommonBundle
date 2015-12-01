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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Infinite\CommonBundle\Activity\ActivityLogger;
use Infinite\CommonBundle\Activity\ActivityLoggerInterface;
use Infinite\CommonBundle\Activity\DoctrineActivityLogger;
use Psr\Log\LoggerInterface;

class DoctrineActivityLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineActivityLogger
     */
    private $logger;

    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $open;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $psrLogger;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    public $managerRegistry;

    /**
     * @var \Raven_Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $raven;

    /**
     * @var ActivityLoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $wrappedLogger;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->managerRegistry = $this->getMock('Doctrine\\Common\\Persistence\\ManagerRegistry');
        $this->psrLogger = $this->getMock('Psr\\Log\\LoggerInterface');
        $this->raven = $this->getMockBuilder('Raven_Client')
            ->disableOriginalConstructor()
            ->getMock();

        $this->open = $this->getMock('Doctrine\\ORM\\EntityManagerInterface');
        $this->open->expects($this->any())
            ->method('isOpen')
            ->willReturn(true);
        $this->managerRegistry->expects($this->any())
            ->method('getManager')
            ->with(null)
            ->willReturn($this->open);

        $this->wrappedLogger = new ActivityLogger($this->psrLogger, $this->raven);
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

    /**
     * @expectedException \Infinite\CommonBundle\Activity\FailedActivityException
     */
    public function testLogCallableRollbackOnFailure()
    {
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
