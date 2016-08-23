<?php

/**
 * This file is part of the InfiniteCommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Date;

class IntervalFormatter
{
    /**
     * Formats a DateInterval as an IntervalSpec
     *
     * @param \DateInterval $interval
     * @return string
     */
    public static function formatDateInterval(\DateInterval $interval)
    {
        $spec = 'P';

        if ($interval->y) {
            $spec .= $interval->y . 'Y';
        }

        if ($interval->m) {
            $spec .= $interval->m . 'M';
        }

        if ($interval->d) {
            $spec .= $interval->d . 'D';
        }

        if ($interval->h || $interval->i || $interval->s) {
            $spec .= 'T';

            if ($interval->h) {
                $spec .= $interval->h . 'H';
            }

            if ($interval->i) {
                $spec .= $interval->i . 'M';
            }

            if ($interval->s) {
                $spec .= $interval->s . 'S';
            }
        }

        return $spec;
    }
}
