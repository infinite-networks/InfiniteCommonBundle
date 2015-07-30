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

class FailedActivityException extends \Exception
{
    /**
     * @var string
     */
    private $activityDescription;

    /**
     * @var array
     */
    private $context;

    public function __construct(\Exception $e, $activityDescription, array $context = [])
    {
        $message = sprintf('An error occurred while trying to perform activity (%s): %s', $activityDescription, $e->getMessage());
        parent::__construct($message, $e->getCode(), $e);

        $this->activityDescription = $activityDescription;
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getActivityDescription()
    {
        return $this->activityDescription;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}