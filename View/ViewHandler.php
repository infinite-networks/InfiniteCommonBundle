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

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler as BaseViewHandler;

class ViewHandler extends BaseViewHandler
{
    protected function getFormFromView(View $view)
    {
        $data = $view->getData();

        if (array_key_exists('data', $data) and method_exists($data['data'], 'getRawForm')) {
            return $data['data']->getRawForm();
        }

        return parent::getFormFromView($view);
    }
}
