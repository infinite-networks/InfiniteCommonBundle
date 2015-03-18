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

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class RequestInformationListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var int
     */
    private $level;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     * @param int $level
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger, $level = 200)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->level = $level;
    }

    /**
     * @DI\Observe("kernel.request", priority=-255)
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $data = $this->getData($event);

        if ($data) {
            $this->logger->log($this->level, 'Request', $data);
        }
    }

    /**
     * Populates an array with data that is relevant for basic logging of a request
     * that has been made.
     *
     * @param GetResponseEvent $event
     * @return array
     */
    protected function getData(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $token = $this->container->get('security.token_storage')->getToken();
        $data = array(
            'clientIp' => $request->getClientIp(),
            'method' => $request->getMethod(),
            'requestUri' => $request->getRequestUri(),
        );

        $user = $token->getUser();
        if ($user && $user instanceof UserInterface) {
            $data['roles'] = $user->getRoles();
            $data['username'] = $user->getUsername();
        }

        return $data;
    }
}
