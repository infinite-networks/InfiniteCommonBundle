<?php

/**
 * This file is part of an Infinite library
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Date;

class DateArithmetic
{
    /**
     * Add a number of months to the target DateTime, clamping to the target month if necessary.
     *
     * Example:  2013-01-31  +  1 month  =  2013-02-28
     *
     * @param \DateTime $dateTime
     * @param $months
     * @return \DateTime $dateTime
     */
    public static function addMonths(\DateTime $dateTime, $months)
    {
        $dateTime = clone $dateTime;
        $dayOfMonth = $dateTime->format('j');
        $dateTime->modify('- ' . ($dayOfMonth - 1) . ' days + ' . $months . ' months');

        $daysInTargetMonth = $dateTime->format('t');

        $dateTime->modify('+ '.(min($dayOfMonth, $daysInTargetMonth) - 1).' days');

        return $dateTime;
    }

    /**
     * Computes the next "monthiversary" (analogous to anniversaries).
     * This is equal to startDate + (n+1) months, where n is the number of months between startDate and lastDate.
     *
     * @param \DateTime $startDate
     * @param \DateTime $lastDate
     * @return \DateTime
     */
    public static function nextMonthiversary(\DateTime $startDate, \DateTime $lastDate)
    {
        $dateTime = clone $lastDate;
        $dateTime->modify('- ' . ($dateTime->format('j') - 1) . ' days + 1 month');

        $daysInTargetMonth = $dateTime->format('t');

        $dateTime->modify('+ '.(min($startDate->format('j'), $daysInTargetMonth) - 1).' days');

        return $dateTime;
    }

    /**
     * Computes the previous "monthiversary" (analogous to anniversaries).
     * This is equal to startDate + (n-1) months, where n is the number of months between startDate and lastDate.
     *
     * @param \DateTime $startDate
     * @param \DateTime $lastDate
     * @return \DateTime
     */
    public static function previousMonthiversary(\DateTime $startDate, \DateTime $lastDate)
    {
        $dateTime = clone $lastDate;
        $dateTime->modify('- ' . ($dateTime->format('j') - 1) . ' days - 1 month');

        $daysInTargetMonth = $dateTime->format('t');

        $dateTime->modify('+ '.(min($startDate->format('j'), $daysInTargetMonth) - 1).' days');

        return $dateTime;
    }
}
