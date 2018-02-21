<?php

namespace Tms\Bundle\RestBundle\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * RequestProvider is a basic implementation of a
 * provider of request.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class RequestProvider implements RequestProviderInterface
{
    /**
     * The services container.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container The services container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function provide()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }
}
