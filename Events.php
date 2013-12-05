<?php

/**
 * This file is part of the Watchlister project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle;

final class Events
{
    /**
     * An event that is fired when the menu is being configured for
     * other bundles to add their menu items to.
     */
    const MENU_CONFIGURE = 'infinite_common.menu_configure';
}
