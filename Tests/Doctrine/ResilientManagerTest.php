<?php

/**
 * This file is part of the Infinite Invocation.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Tests\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Infinite\CommonBundle\Doctrine\ResilientManager;

class ResilientManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResilientManager
     */
    private $manager;

    /**
     * @var \Raven_Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $raven;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\\Common\\Persistence\\ManagerRegistry');
        $this->raven = $this->getMockBuilder('Raven_Client')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ResilientManager($this->registry, $this->raven);
    }

    public function testResettingManagers()
    {
        $closed = $this->getEntityManager(false);
        $open = $this->getEntityManager(true);

        $this->registry->expects($this->exactly(2))
            ->method('getManager')
            ->with('named')
            ->willReturnOnConsecutiveCalls(
                $closed,
                $open
            );

        $manager = $this->manager->getManager('named');

        $this->assertSame($open, $manager);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailingToResetManagers()
    {
        $closed = $this->getEntityManager(false);
        $closed2 = $this->getEntityManager(false);

        $this->registry->expects($this->exactly(2))
            ->method('getManager')
            ->with('named')
            ->willReturnOnConsecutiveCalls(
                $closed,
                $closed2
            );

        $this->manager->getManager('named');
    }

    public function testWrapCallable()
    {
        $open = $this->getEntityManager(true);

        $this->registry->expects($this->exactly(1))
            ->method('getManager')
            ->with('named')
            ->willReturn($open);

        $mock = $this->getMock('stdClass', ['callback']);
        $mock->expects($this->once())
            ->method('callback')
            ->with($open);

        $open->expects($this->once())
            ->method('beginTransaction');
        $open->expects($this->once())
            ->method('flush');
        $open->expects($this->once())
            ->method('commit');

        $this->manager->wrapCallable([$mock, 'callback'], null, 'named');
    }

    public function testWrapCallableHandlesException()
    {
        $open = $this->getEntityManager(true);

        $this->registry->expects($this->exactly(1))
            ->method('getManager')
            ->with('named')
            ->willReturn($open);

        $mock = $this->getMock('stdClass', ['callback']);
        $mock->expects($this->once())
            ->method('callback')
            ->with($open);

        $open->expects($this->once())
            ->method('beginTransaction');
        $open->expects($this->once())
            ->method('flush');
        $open->expects($this->once())
            ->method('commit')
            ->willThrowException($e = new ORMException());
        $open->expects($this->once())
            ->method('rollback');
        $this->raven->expects($this->once())
            ->method('captureException')
            ->with($e);

        $this->manager->wrapCallable([$mock, 'callback'], null, 'named');
    }

    public function testWrapCallableCallsOnFailure()
    {
        $open = $this->getEntityManager(true);

        $this->registry->expects($this->exactly(1))
            ->method('getManager')
            ->with('named')
            ->willReturn($open);
        $open->expects($this->once())
            ->method('commit')
            ->willThrowException($e = new ORMException());

        $mock = $this->getMock('stdClass', ['callback', 'onFailure']);
        $mock->expects($this->once())
            ->method('callback')
            ->with($open);
        $mock->expects($this->once())
            ->method('onFailure')
            ->with($e);

        $this->manager->wrapCallable([$mock, 'callback'], [$mock, 'onFailure'], 'named');
    }

    public function testWrapPersist()
    {
        $open = $this->getEntityManager(true);

        $this->registry->expects($this->exactly(1))
            ->method('getManager')
            ->with(null)
            ->willReturn($open);

        $obj = new \stdClass;
        $open->expects($this->once())
            ->method('persist')
            ->with($obj);

        $this->manager->wrapPersist($obj);
    }

    public function testWrapPersistWrapsOnFailure()
    {
        $open = $this->getEntityManager(true);

        $this->registry->expects($this->exactly(1))
            ->method('getManager')
            ->with('named')
            ->willReturn($open);
        $open->expects($this->once())
            ->method('commit')
            ->willThrowException($e = new ORMException());

        $obj = new \stdClass;

        $mock = $this->getMock('stdClass', ['onFailure']);
        $mock->expects($this->once())
            ->method('onFailure')
            ->with($e, $obj);

        $this->manager->wrapPersist($obj, [$mock, 'onFailure'], 'named');
    }

    /**
     * @expectedException \Doctrine\ORM\ORMException
     */
    public function testWrapClosureRethrowsORMException()
    {
        $open = $this->getEntityManager(true);

        $this->registry->expects($this->exactly(1))
            ->method('getManager')
            ->with('named')
            ->willReturn($open);
        $open->expects($this->once())
            ->method('commit')
            ->willThrowException($e = new ORMException());

        $mock = $this->getMock('stdClass', ['callback', 'onFailure']);
        $mock->expects($this->once())
            ->method('callback')
            ->with($open);
        $mock->expects($this->once())
            ->method('onFailure')
            ->with($e)
            ->willReturn(true);

        $this->manager->wrapCallable([$mock, 'callback'], [$mock, 'onFailure'], 'named');
    }

    /**
     * @expectedException \Doctrine\ORM\ORMException
     */
    public function testWrapPersistRethrowsOnFailure()
    {
        $open = $this->getEntityManager(true);

        $this->registry->expects($this->exactly(1))
            ->method('getManager')
            ->with('named')
            ->willReturn($open);
        $open->expects($this->once())
            ->method('commit')
            ->willThrowException($e = new ORMException());

        $obj = new \stdClass;

        $mock = $this->getMock('stdClass', ['onFailure']);
        $mock->expects($this->once())
            ->method('onFailure')
            ->with($e, $obj)
            ->willReturn(true);

        $this->manager->wrapPersist($obj, [$mock, 'onFailure'], 'named');
    }

    /**
     * @param bool $open
     * @return EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getEntityManager($open)
    {
        $em = $this->getMock('Doctrine\\ORM\\EntityManagerInterface');
        $em->expects($this->any())
            ->method('isOpen')
            ->willReturn($open);

        return $em;
    }
}
