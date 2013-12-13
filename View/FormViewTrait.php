<?php

/**
 * This file is part of the Watchlister project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\View;

use Symfony\Component\Form\FormInterface;

trait FormViewTrait
{
    /**
     * @var \Symfony\Component\Form\FormInterface
     */
    protected $form;

    /**
     * @var \Symfony\Component\Form\FormView
     */
    private $formView;

    /**
     * Returns the form view.
     *
     * @return \Symfony\Component\Form\FormView
     */
    public function getForm()
    {
        if (!$this->formView) {
            $this->formView = $this->form->createView();
        }

        return $this->formView;
    }

    public function setForm(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * Returns the raw form.
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getRawForm()
    {
        return $this->form;
    }
}
