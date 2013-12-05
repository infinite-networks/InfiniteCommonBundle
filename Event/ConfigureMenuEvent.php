<?php

/**
 * This file is part of the Infinite CommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\Event;

class ConfigureMenuEvent extends Event
{
    private $factory;
    private $menu;
    private $menuName;

    /**
     * @param FactoryInterface $factory
     * @param ItemInterface $menu
     * @param string $menuName
     */
    public function __construct(FactoryInterface $factory, ItemInterface $menu, $menuName)
    {
        $this->factory = $factory;
        $this->menu = $menu;
        $this->menuName = $menuName;
    }

    /**
     * @return \Knp\Menu\FactoryInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu
     */
    public function setMenu($menu)
    {
        $this->menu = $menu;
    }

    /**
     * @return string
     */
    public function getMenuName()
    {
        return $this->menuName;
    }
}
