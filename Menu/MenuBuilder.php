<?php

/**
 * This file is part of the Infinite CommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Menu;

use Infinite\CommonBundle\Event\ConfigureMenuEvent;
use Infinite\CommonBundle\Events;
use Knp\Menu\FactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles creation of new menus.
 */
class MenuBuilder
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var \Knp\Menu\FactoryInterface
     */
    private $factory;

    public function __construct(EventDispatcherInterface $dispatcher, FactoryInterface $factory)
    {
        $this->dispatcher = $dispatcher;
        $this->factory = $factory;
    }

    public function buildMenu($menuName)
    {
        $menu = $this->factory->createItem('root');

        $event = new ConfigureMenuEvent($this->factory, $menu, $menuName);
        $this->dispatcher->dispatch(Events::MENU_CONFIGURE, $event);

        return $event->getMenu();
    }
}
