<?php

/**
 * This file is part of the Infinite CommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\View;

use FOS\RestBundle\View\View as BaseView;
use JMS\Serializer\SerializationContext;

class View extends BaseView
{
    public static function create($data = null, $statusCode = null, array $headers = array(), array $groups = array())
    {
        $view = parent::create($data, $statusCode, $headers);

        if ($groups) {
            $context = new SerializationContext();
            $context->setGroups($groups);
            $view->setSerializationContext($context);
        }

        return $view;
    }
}
