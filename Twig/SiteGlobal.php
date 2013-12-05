<?php

/**
 * This file is part of the Infinite CommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Custom global variable that provides additional properties to use
 * for site information.
 */
class SiteGlobal implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Stores breadcrumbs for display.
     *
     * @var \SplQueue
     */
    public $breadcrumbs;

    /**
     * Stores the current request time.
     *
     * @var \DateTime
     */
    public $date;

    /**
     * Stores an array of date formats to be used inside twig.
     *
     * @var array<string>
     */
    private $formats;

    public function __construct()
    {
        $this->breadcrumbs = new \SplQueue;
        $this->date = new \DateTime;
    }

    /**
     * Returns an array of date format strings.
     *
     * @return array
     */
    public function getFormats()
    {
        if (null === $this->formats) {
            $this->formats = array(
                'date' => $this->container->getParameter('format.date'),
                'datetime' => $this->container->getParameter('format.datetime'),
                'shortdate' => $this->container->getParameter('format.shortdate'),
                'shorttime' => $this->container->getParameter('format.shorttime'),
                'time' => $this->container->getParameter('format.time'),
            );
        }

        return $this->formats;
    }

    /**
     * The name of the site.
     *
     * @return string
     */
    public function getName()
    {
        return $this->container->getParameter('site.name');
    }

    /**
     * The short name of the site.
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->container->getParameter('site.short_name');
    }

    /**
     * The application version string. Requires some additional processing in the Kernel
     * to be available.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->container->getParameter('site.version');
    }
}
