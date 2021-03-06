<?php

/**
 * This file is part of the Infinite CommonBundle project.
 *
 * (c) Infinite Networks Pty Ltd <http://www.infinite.net.au>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Infinite\CommonBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class RavenInformationListener
{
    private $raven;
    private $tokenStorage;
    private $version;

    /**
     * @param \Raven_Client $raven
     * @param TokenStorageInterface $tokenStorage
     * @param string $version
     */
    public function __construct(\Raven_Client $raven, TokenStorageInterface $tokenStorage = null, $version = null)
    {
        $this->raven = $raven;
        $this->tokenStorage = $tokenStorage;
        $this->version = $version;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $data = array(
            'version' => $this->version
        );

        $token = $this->tokenStorage ?
            $this->tokenStorage->getToken() :
            null;
        if ($token && $token->getUser() instanceof UserInterface) {
            $user = $token->getUser();
            $this->raven->user_context(array(
                'username' => $user->getUsername(),
            ));
        }

        $this->raven->extra_context($data);
    }
}
