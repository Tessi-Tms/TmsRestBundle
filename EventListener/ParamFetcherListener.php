<?php

namespace Tms\Bundle\RestBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Tms\Bundle\RestBundle\ConfigurationParamFetcher;

/**
 * This listener change default parameters values based on the configuration.
 *
 * @author Gabriel Bondaz <gabriel.bondaz@idci-consulting.fr>
 */
class ParamFetcherListener
{
    private $configurationParamFetcher;

    /**
     * Constructor.
     *
     * @param ConfigurationParamFetcher $configurationParamFetcher
     */
    public function __construct(ConfigurationParamFetcher $configurationParamFetcher)
    {
        $this->configurationParamFetcher = $configurationParamFetcher;
    }

    /**
     * Core controller handler.
     *
     * @param FilterControllerEvent $event
     *
     * @throws \InvalidArgumentException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $routeName = $request->get('_route');

        $fetchedConfiguration = $this
            ->configurationParamFetcher
            ->fetch($routeName)
        ;

        foreach ($fetchedConfiguration as $key => $parameters) {
            $value = $request->attributes->get($key);
            $this
                ->configurationParamFetcher
                ->fetchDefaultValue($routeName, $key, $value)
            ;
            $request->attributes->set($key, $value);
        }
    }
}
