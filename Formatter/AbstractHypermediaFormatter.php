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
use JMS\Serializer\Serializer;
use FOS\RestBundle\Routing\Loader\Reader\RestControllerReader;
use Tms\Bundle\RestBundle\Criteria\CriteriaBuilder;
use Tms\Bundle\RestBundle\Request\ParamReaderProvider;

abstract class AbstractHypermediaFormatter
{
    const SERIALIZER_CONTEXT_GROUP_ITEM = 'tms_rest.item';
    const SERIALIZER_CONTEXT_GROUP_COLLECTION = 'tms_rest.collection';
    const SERIALIZER_CONTEXT_GROUP_NAME = 'serializerContextGroup';

    // Services
    protected $serializer;
    protected $router;
    protected $criteriaBuilder;
    protected $controllerReader;
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
        RestControllerReader $controllerReader,
        ParamReaderProvider $paramReaderProvider,
        $currentRouteName,
        $format
    )
    {
        // Services
        $this->router = $router;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->serializer = $serializer;
        $this->controllerReader = $controllerReader;
        $this->paramReaderProvider = $paramReaderProvider;

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
        return array(
            'metadata' => $this->formatMetadata(),
            'data'     => $this->formatData(),
            'links'    => $this->formatLinks(),
            'actions'  => $this->formatActions()
        );
    }

    /**
     * Format raw data to have hypermedia metadata in output
     *
     * @return array
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
     * @return array
     */
    abstract protected function formatData();

    /**
     * Format raw data to have hypermedia links in output
     *
     * @return array
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
     * @return array
     */
    protected function formatActions()
    {
        $actions = $this->formatControllersActions();
var_dump($actions);die;
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

        foreach ($this->controllers as $controller) {
            $paramReader = $this->paramReaderProvider->provide();
            $class = new \ReflectionClass($controller);

            foreach ($class->getMethods() as $method) {
                $methodName = $method->getName();
                $actionMethodSuffix = 'Action';

                $routeCollection = $this->controllerReader->read($class);

                if ($actionMethodSuffix === substr($methodName, 0 - strlen($actionMethodSuffix))) {
                    foreach ($routeCollection->all() as $routeName => $route) {
                        if ($methodName === $route->getDefault('_controller')) {
                            $actionName = $routeName;
                        }
                    }

                    if (isset($actionName)) {
                        $route = $routeCollection->get($actionName);
                        $httpMethods = $route->getMethods();
                        $url = sprintf('http://%s%s',
                            $route->getHost(),
                            $route->getPath()
                        );

                        $requiredParams = array();
                        $optionalParams = array();

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
