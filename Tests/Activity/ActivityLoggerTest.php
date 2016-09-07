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
use Infinite\CommonBundle\Activity\Context;
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
        $this->psrLogger = $this->createMock(LoggerInterface::class);
        $this->raven = $this->createMock(\Raven_Client::class);

        $this->logger = new ActivityLogger($this->psrLogger, $this->raven);
    }

    public function testLogCallableSucceedsOnNull()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', []);

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
            ->with(300, 'Testing Callable', ['result' => ['test' => 'value']]);

        $this->logger->logCallable('Testing Callable', function () { return ['test' => 'value']; });
    }

    public function testLogCallableSucceedsWithProvidedContext()
    {
        $context = new Context(['test' => 'value']);

        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', ['test' => 'value']);

        $this->logger->logCallable('Testing Callable', function () { }, $context);
    }

    public function testLogCallableSucceedsReturningObject()
    {
        $class = new \stdClass;
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', ['result' => $class]);

        $result = $this->logger->logCallable('Testing Callable', function () use ($class) { return $class; });

        $this->assertSame($result, $class);
    }

    public function testLogCallableSucceedsWithAdditionalCallback()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', ['cool' => 'yep']);

        $this->logger->logCallable('Testing Callable', function (Context $data) { $data['cool'] = 'yep'; });
    }

    public function testLogCallableSendsArrayByReference()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(300, 'Testing Callable', ['cool' => 'yep']);

        $this->logger->logCallable('Testing Callable', function ($data) { $data['cool'] = 'yep'; });
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
            $this->logger->logCallable('Testing Failed Callable', function ($data) use ($innerException) {
                $data['huh'] = 'wat';
                throw $innerException;
            });
        } catch (FailedActivityException $e) {
            $this->assertContains('Failure!', $e->getMessage());
            $this->assertContains('Testing Failed Callable', $e->getMessage());
            $this->assertSame($innerException, $e->getPrevious());
            $this->assertEquals('wat', $e->getContext()['huh']);

            return;
        }

        $this->fail('Correct exception was not thrown');
    }

    public function testCombineContextOnNestedFailures()
    {
        try {
            $this->logger->logCallable('Outer', function () {
                throw new FailedActivityException(new \Exception(), 'Inner', new Context(['inner' => 1, 'test' => 'val']));
            }, new Context(['test' => 'hello']));
        } catch (FailedActivityException $e) {
            $this->assertArrayHasKey('test', $e->getContext());
            $this->assertArrayHasKey('inner', $e->getContext());

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
            $this->assertContains('Outer', $wrapped->getActivityDescription());

            return true;
        });
    }

    public function testNotHandledTwice()
    {
        $this->psrLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                [400, $this->stringContains('Inner'), $this->isType('array')],
                [300, $this->stringContains('Outer'), $this->isType('array')]
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

    public function testLogsAtRoot()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->withConsecutive(
                [300, $this->stringContains('Outer'), $this->isType('array')]
            );

        $this->logger->logCallable('Outer', function () {
            $this->logger->logCallable('Inner', function () { });
        });
    }

    public function testOverrideRootLogging()
    {
        $this->psrLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                [300, $this->stringContains('Inner'), $this->isType('array')],
                [300, $this->stringContains('Outer'), $this->isType('array')]
            );

        $this->logger->logCallable('Outer', function () {
            $this->logger->logCallable('Inner', function () { }, [], null, true);
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

    public function testSuppressSuccessfulLog()
    {
        $this->psrLogger->expects($this->never())
            ->method('log');

        $this->logger->logCallable('Test', function () { xdebug_break(); }, [], null, false);
    }

    public function testRemovingContext()
    {
        $context = new Context(['wooo' => 'hooo']);

        $this->logger->logCallable('Test', function (Context $context) { unset($context['wooo']); }, $context);

        $this->assertArrayNotHasKey('wooo', $context);
    }

    public function testContextStoresResult()
    {
        $context = new Context();

        $this->logger->logCallable('Test', function () { return false; }, $context);

        $this->assertFalse($context->getResult());
    }

    public function testSupportsBooleanForSwallow()
    {
        $this->logger->logCallable('Test', function () { throw new \Exception; }, null, true);
    }

    public function testLogLevelInteger()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(500, 'Testing Callable', ['result' => true]);

        $this->logger->logCallable('Testing Callable', function () { return true; }, null, null, 500);
    }

    public function testLogLevelIntegerCallback()
    {
        $this->psrLogger->expects($this->once())
            ->method('log')
            ->with(500, 'Testing Callable', ['result' => true]);

        $this->logger->logCallable('Testing Callable', function () { return true; }, null, null, function () { return 500; });
    }
}
