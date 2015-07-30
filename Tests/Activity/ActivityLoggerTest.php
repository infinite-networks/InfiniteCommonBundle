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

use Infinite\CommonBundle\Activity\ActivityLogger;
use Infinite\CommonBundle\Activity\FailedActivityException;
use Psr\Log\LoggerInterface;

class ActivityLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActivityLogger
     */
    private $logger;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $psrLogger;

    /**
     * @var \Raven_Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $raven;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->psrLogger = $this->getMock('Psr\\Log\\LoggerInterface');
        $this->raven = $this->getMockBuilder('Raven_Client')
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = new ActivityLogger($this->psrLogger, $this->raven);
    }

    public function testLogCallableReturnsFalse()
    {
        $this->psrLogger->expects($this->never())
            ->method('log');

        $this->logger->logCallable('Testing Callable', function () { return false; });
    }

    public function testLogCallableSucceedsOnNull()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', ['result' => null]);

        $this->logger->logCallable('Testing Callable', function () { });
    }

    public function testLogCallableSucceedsOnTrue()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', ['result' => true]);

        $this->logger->logCallable('Testing Callable', function () { return true; });
    }

    public function testLogCallableSucceedsReturningContext()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', ['test' => 'value']);

        $this->logger->logCallable('Testing Callable', function () { return ['test' => 'value']; });
    }

    public function testLogCallableSucceedsWithProvidedContext()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', ['test' => 'value', 'result' => null]);

        $this->logger->logCallable('Testing Callable', function () { }, ['test' => 'value']);
    }

    public function testLogCallableSucceedsReturningObject()
    {
        $class = new \stdClass;
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', ['result' => $class]);

        $this->logger->logCallable('Testing Callable', function () use ($class) { return $class; });
    }

    public function testLogCallableSucceedsWithAdditionalCallback()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', ['cool' => 'yep', 'result' => null]);

        $this->logger->logCallable('Testing Callable', function (&$data) { $data['cool'] = 'yep'; });
    }

    public function testLogCallableFails()
    {
        $innerException = new \Exception('Failure!');

        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(400, $this->logicalAnd(
                $this->stringContains('Testing Failed Callable'),
                $this->stringContains('Failure!')
            ), []);

        try {
            $this->logger->logCallable('Testing Failed Callable', function () use ($innerException) { throw $innerException; });
        } catch (FailedActivityException $e) {
            $this->assertContains('Failure!', $e->getMessage());
            $this->assertContains('Testing Failed Callable', $e->getMessage());
            $this->assertSame($innerException, $e->getPrevious());

            return;
        }

        $this->fail('Correct exception was not thrown');
    }

    public function testLogCallableFailsWithAdditionalContext()
    {
        $innerException = new \Exception('Failure!');

        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(400, $this->logicalAnd(
                $this->stringContains('Testing Failed Callable'),
                $this->stringContains('Failure!')
            ), ['huh' => 'wat']);

        try {
            $this->logger->logCallable('Testing Failed Callable', function (&$data) use ($innerException) {
                $data['huh'] = 'wat';
                throw $innerException;
            });
        } catch (FailedActivityException $e) {
            $this->assertContains('Failure!', $e->getMessage());
            $this->assertContains('Testing Failed Callable', $e->getMessage());
            $this->assertSame($innerException, $e->getPrevious());
            $this->assertEquals(['huh' => 'wat'], $e->getContext());

            return;
        }

        $this->fail('Correct exception was not thrown');
    }

    public function testCombineContextOnNestedFailures()
    {
        try {
            $this->logger->logCallable('Outer', function () {
                throw new FailedActivityException(new \Exception(), 'Inner', ['inner' => 1, 'test' => 'val']);
            }, ['test' => 'hello']);
        } catch (FailedActivityException $e) {
            $this->assertEquals(['test' => 'hello', 'inner' => 1], $e->getContext());

            return;
        }

        $this->fail('Correct exception was not thrown');
    }

    public function testSwallowException()
    {
        $this->raven->expects($this->once())
            ->method('captureException')
            ->with($this->isInstanceOf('Infinite\\CommonBundle\\Activity\\FailedActivityException'), ['extra' => ['test' => 'hello']]);

        $this->logger->logCallable('Outer', function () {
            throw new \Exception;
        }, ['test' => 'hello'], function ($e, $wrapped) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertInstanceOf('Infinite\\CommonBundle\\Activity\\FailedActivityException', $wrapped);
            return true;
        });
    }

    public function testNotHandledTwice()
    {
        $this->psrLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                [400, $this->isType('string'), $this->isType('array')],
                [300, $this->isType('string'), $this->isType('array')]
            );
        $this->raven->expects($this->once())
            ->method('captureException')
            ->with($this->isInstanceOf('Infinite\\CommonBundle\\Activity\\FailedActivityException'), ['extra' => []]);

        $this->logger->logCallable('Outer', function () {
            $this->logger->logCallable('Inner', function () {
                throw new \Exception;
            }, [], function () { return true; });
        }, [], function () {
            $this->fail('Inner handler leaked');
        });
    }

    public function testDoesNotHandlePHPUnitExceptions()
    {
        try {
            $this->logger->logCallable('Inner', function () {
                throw new \PHPUnit_Framework_Exception;
            }, [], function () { return true; });
        } catch (\PHPUnit_Exception $e) {
            return;
        }

        $this->fail('Swallowed PHPUnit Exception');
    }

    public function testLogToOutput()
    {
        $output = $this->getMock('Symfony\\Component\\Console\\Output\\OutputInterface');
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('Test'));

        $this->logger->setOutput($output);
        $this->logger->logCallable('Test', function () { });
    }
}
