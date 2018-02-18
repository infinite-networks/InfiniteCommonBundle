<?php

/**
 * This file is part of the Infinite InfiniteCommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Tests;

use Infinite\CommonBundle\Activity\AddContextExceptionInterface;
use Infinite\CommonBundle\Activity\Context;

class AddContextException extends \Exception implements AddContextExceptionInterface
{
    public function addToContext(Context $context)
    {
        $context['test'] = 'added';
    }
}
