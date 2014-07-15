<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

use Tms\Bundle\RestBundle\Criteria\CriteriaBuilder;
use Symfony\Component\Routing\Router;
use JMS\Serializer\Serializer;

abstract class AbstractHypermediaFormatter
{
    const SERIALIZER_CONTEXT_GROUP_ITEM = 'tms_rest.item';
    const SERIALIZER_CONTEXT_GROUP_COLLECTION = 'tms_rest.collection';
    const SERIALIZER_CONTEXT_GROUP_NAME = 'serializerContextGroup';

    // Services
    protected $serializer;
    protected $router;
    protected $criteriaBuilder;

    // Formatters default attributes
    protected $currentRouteName;
    protected $format;

    // Actions
    protected $actions = array();

    /**
     * Constructor
     */
    public function __construct(Router $router, CriteriaBuilder $criteriaBuilder, Serializer $serializer, $currentRouteName, $format)
    {
        // Services
        $this->router = $router;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->serializer = $serializer;

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
    public function setAction(
        $name,
        $method,
        $url,
        array $requiredParams = array(),
        array $optionalParams = array()
    )
    {
        $this->actions[$name] = array(
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
        $actions = array();

        foreach ($this->actions as $name => $action) {
            $actions[$name] = array(
                'rel' => $name,
                'href' => $action['href'],
                'method' => $action['method'],
                'requiredParams' => $action['requiredParams'],
                'optionalParams' => $action['optionalParams']
            );
        }

        return $actions;
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
