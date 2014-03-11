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

use Doctrine\Common\Util\Debug;
use Twig_SimpleFunction;

class DebugExtension extends \Twig_Extension_Debug
{
    public function getFunctions()
    {
        // dump is safe if var_dump is overridden by xdebug
        $isDumpOutputHtmlSafe = extension_loaded('xdebug')
            // false means that it was not set (and the default is on) or it explicitly enabled
            && (false === ini_get('xdebug.overload_var_dump') || ini_get('xdebug.overload_var_dump'))
            // false means that it was not set (and the default is on) or it explicitly enabled
            // xdebug.overload_var_dump produces HTML only when html_errors is also enabled
            && (false === ini_get('html_errors') || ini_get('html_errors'))
            || 'cli' === php_sapi_name()
        ;

        $functions = parent::getFunctions();
        $functions[] = new Twig_SimpleFunction('doctrine_dump', array($this, 'doctrineDump'), array(
            'is_safe' => $isDumpOutputHtmlSafe ? array('html') : array(),
            'needs_context' => true,
            'needs_environment' => true
        ));
        $functions[] = new Twig_SimpleFunction('dd', array($this, 'doctrineDump'), array(
            'is_safe' => $isDumpOutputHtmlSafe ? array('html') : array(),
            'needs_context' => true,
            'needs_environment' => true
        ));

        return $functions;
    }

    /**
     * Code borrowed from Twig_Extension_Debug
     *
     * @param \Twig_Environment $env
     * @param array $context
     * @return string
     */
    public function doctrineDump(\Twig_Environment $env, $context)
    {
        if (!$env->isDebug()) {
            return '';
        }

        ob_start();

        $count = func_num_args();
        if (2 === $count) {
            $vars = array();
            foreach ($context as $key => $value) {
                if (!$value instanceof \Twig_Template) {
                    $vars[$key] = $value;
                }
            }

            Debug::dump($vars);
        } else {
            $depth = ($count > 3 and is_integer(func_get_arg($count - 1))) ? func_get_arg($count - 1) : 3;
            for ($i = 2; $i < $count; $i++) {
                Debug::dump(func_get_arg($i), $depth);
            }
        }

        return ob_get_clean();
    }
}
