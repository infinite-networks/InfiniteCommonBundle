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

class SiteExtension extends \Twig_Extension
{
    private $siteGlobal;

    /**
     * @var string
     */
    private $siteVariable;

    public function __construct(SiteGlobal $siteGlobal, $siteVariable = 'site')
    {
        $this->siteGlobal = $siteGlobal;
        $this->siteVariable = $siteVariable;
    }

    public function getGlobals()
    {
        return array(
            $this->siteVariable => $this->siteGlobal,
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'infinite_common_site';
    }
}
