<?php

/**
 * This file is part of the Infinite CommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Adds created at and updated at timestamps to entities.
 *
 * @ORM\HasLifecycleCallbacks
 */
trait Timestampable
{
    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $dateAdded;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $dateUpdated;

    /**
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * @ORM\PrePersist
     */
    public function _timestampable_prePersist()
    {
        $this->dateAdded = new \DateTime;
        $this->dateUpdated = new \DateTime;
    }

    /**
     * @ORM\PreUpdate
     */
    public function _timestampable_preUpdate()
    {
        $this->dateUpdated = new \DateTime;
    }
}
