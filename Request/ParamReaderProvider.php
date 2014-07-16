<?php

namespace Tms\Bundle\RestBundle\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ParamReaderProvider is a basic implementation of a 
 * provider of param reader.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class ParamReaderProvider implements ParamReaderProviderInterface
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
        return $this->container->get('fos_rest.request.param_fetcher.reader');
    }
}
