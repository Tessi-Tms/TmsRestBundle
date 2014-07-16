<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Route;
use Symfony\Component\Config\Loader\LoaderInterface;
use JMS\Serializer\Serializer;
use Tms\Bundle\RestBundle\Criteria\CriteriaBuilder;
use Tms\Bundle\RestBundle\Request\ParamReaderProviderInterface;
use Tms\Bundle\RestBundle\Request\RequestProviderInterface;

abstract class AbstractHypermediaFormatter
{
    const SERIALIZER_CONTEXT_GROUP_ITEM = 'tms_rest.item';
    const SERIALIZER_CONTEXT_GROUP_COLLECTION = 'tms_rest.collection';
    const SERIALIZER_CONTEXT_GROUP_NAME = 'serializerContextGroup';

    // Services
    protected $serializer;
    protected $router;
    protected $criteriaBuilder;
    protected $routeLoader;
    protected $paramReaderProvider;

    // Formatters default attributes
    protected $currentRouteName;
    protected $format;

    // Actions
    protected $actions = array();
    protected $controllers = array();

    /**
     * Constructor
     */
    public function __construct(
        Router $router,
        CriteriaBuilder $criteriaBuilder,
        Serializer $serializer,
        LoaderInterface $routeLoader,
        ParamReaderProviderInterface $paramReaderProvider,
        RequestProviderInterface $requestProvider,
        $currentRouteName,
        $format
    )
    {
        // Services
        $this->router = $router;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->serializer = $serializer;
        $this->routeLoader = $routeLoader;
        $this->paramReaderProvider = $paramReaderProvider;
        $this->requestProvider = $requestProvider;

        // Formatters default attributes
        $this->currentRouteName = $currentRouteName;
        $this->format = $format;

        // Initialize configuration by route
        $this->criteriaBuilder->guessConfigurationByRoute($currentRouteName);
    }

    /**
     * Format raw data to have hypermedia data in output
     *
     * @return array
     */
    public function format()
    {
        $formatting = array();

        $metadata = $this->formatMetadata();
        if (null !== $metadata) {
            $formatting['metadata'] = $metadata;
        }

        $data = $this->formatData();
        if (null !== $data) {
            $formatting['data'] = $data;
        }

        $links = $this->formatLinks();
        if (null !== $links) {
            $formatting['links'] = $links;
        }

        $actions = $this->formatActions();
        if (null !== $actions) {
            $formatting['actions'] = $actions;
        }

        return $formatting;
    }

    /**
     * Format raw data to have hypermedia metadata in output
     *
     * @return array|null
     */
    protected function formatMetadata()
    {
        return array(
            'type' => $this->getType(),
            AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_NAME => $this->getSerializerContextGroup()
        );
    }

    /**
     * Format raw data to have hypermedia data in output
     *
     * @return array|null
     */
    abstract protected function formatData();

    /**
     * Format raw data to have hypermedia links in output
     *
     * @return array|null
     */
    abstract protected function formatLinks();

    /**
     * Set an action.
     *
     * @param string $name           The identifier name.
     * @param string $method         The HTTP method.
     * @param string $url            The url.
     * @param array  $requiredParams The required parameters.
     * @param array  $optionalParams The optional parameters.
     *
     * @return AbstractHypermediaFormatter This.
     */
    public function addAction(
        $name,
        $method,
        $url,
        array $requiredParams = array(),
        array $optionalParams = array()
    )
    {
        $this->actions[] = array(
            'rel' => $name,
            'href' => $url,
            'method' => $method,
            'requiredParams' => $requiredParams,
            'optionalParams' => $optionalParams
        );

        return $this;
    }

    /**
     * Format actions into a given layout for hypermedia
     *
     * @return array|null
     */
    protected function formatActions()
    {
        $actions = $this->formatControllersActions();

        return array_merge($this->actions, $actions);
    }

    /**
     * Add a controller containing related actions
     *
     * @param object $controller A controller class.
     *
     * @return AbstractHypermediaFormatter This.
     */
    public function addActionsController($controller)
    {
        $this->controllers[] = $controller;

        return $this;
    }

    /**
     * Format the actions given by the controllers
     *
     * @return array
     */
    protected function formatControllersActions()
    {
        $actions = array();
        $request = $this->requestProvider->provide();
        $baseUrl = sprintf('%s://%s', $request->getScheme(), $request->getHttpHost());

        foreach ($this->controllers as $controller) {
            $paramReader = $this->paramReaderProvider->provide();
            $localRouteCollection = $this->routeLoader->load($controller);
            $appRouteCollection = $this->router->getRouteCollection();

            foreach ($localRouteCollection as $actionName => $localRoute) {
                foreach ($appRouteCollection as $appRoute) {
                    if ($appRoute->getDefault('_controller') === $localRoute->getDefault('_controller')) {
                        $route = $appRoute;
                    }
                }

                if (isset($route)) {
                    $path = $this->retrieveRoutePath($route);

                    if ($path) {
                        $httpMethods = $route->getMethods();
                        $url = sprintf('%s%s',
                            $baseUrl,
                            $path
                        );

                        $requiredParams = array();
                        $optionalParams = array();
                        list($controllerClass, $controllerMethod) = explode('::', $route->getDefault('_controller'));
                        $method = new \ReflectionMethod($controllerClass, $controllerMethod);

                        foreach ($paramReader->getParamsFromMethod($method) as $name => $param) {
                            if ($param->nullable) {
                                $optionalParams[$name] = $param->requirements;
                            } else {
                                $requiredParams[$name] = $param->requirements;
                            }
                        }

                        foreach ($httpMethods as $httpMethod) {
                            $actions[] = array(
                                'rel' => $actionName,
                                'href' => $url,
                                'method' => $httpMethod,
                                'requiredParams' => $requiredParams,
                                'optionalParams' => $optionalParams
                            );
                        }
                    }
                }
            }

            return $actions;
        }
    }

    /**
     * Retrieve the path of a route.
     *
     * @param Route $route The route.
     *
     * @return string|null The path or null if the route should not be used.
     */
    protected function retrieveRoutePath(Route $route)
    {
        $path = $route->getPath();

        return str_replace(
            array('{_format}'),
            array($this->format),
            $path
        );
    }

    /**
     * Give object type
     *
     * @return string
     */
    abstract protected function getType();

    /**
     * Give object serializerContextGroup
     *
     * @return string
     */
    abstract protected function getSerializerContextGroup();
}
