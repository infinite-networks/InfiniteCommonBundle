<?php

/**
 * This file is part of the Watchlister project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\Form;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FormFactory
{
    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $factory;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    public function __construct(FormFactoryInterface $factory, RequestStack $requestStack)
    {
        $this->factory = $factory;
        $this->requestStack = $requestStack;
    }

    /**
     * Creates a new form.
     *
     * @param string $type
     * @param mixed $data
     * @param array $options
     * @return \Symfony\Component\Form\FormInterface
     */
    public function create($type, $data = null, array $options = array())
    {
        $form = $this->factory->create($type, $data, $options);
        $form->handleRequest($this->requestStack->getCurrentRequest());

        return $form;
    }
}
