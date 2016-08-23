<?php

/**
 * This file is part of the InfiniteCommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;
use Infinite\CommonBundle\Date\IntervalFormatter;

class DateIntervalType extends StringType
{
    const DATEINTERVAL = 'dateinterval';

    /**
     * @param \DateInterval $value
     * @param AbstractPlatform $platform
     * @return string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value ? IntervalFormatter::formatDateInterval($value) : null;
    }

    /**
     * @param string $value
     * @param AbstractPlatform $platform
     * @return \DateInterval
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null !== $value) {
            try {
                return new \DateInterval($value);
            } catch (\Exception $e) {
                throw ConversionException::conversionFailedFormat(
                    $value,
                    $this->getName(),
                    'IntervalSpec'
                );
            }

        }

        return $value;
    }

    public function getName()
    {
        return self::DATEINTERVAL;
    }
}
