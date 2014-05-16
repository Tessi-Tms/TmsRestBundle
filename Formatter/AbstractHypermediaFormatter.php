<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

use Doctrine\Common\Persistence\ObjectManager;
use Tms\Bundle\RestBundle\Criteria\CriteriaBuilder;
use Symfony\Component\Routing\Router;
use JMS\Serializer\Serializer;

abstract class AbstractHypermediaFormatter
{
    const SERIALIZER_CONTEXT_GROUP_ITEM = 'tms_rest.item';
    const SERIALIZER_CONTEXT_GROUP_COLLECTION = 'tms_rest.collection';

    // Services
    protected $serializer;
    protected $router;
    protected $criteriaBuilder;

    // Formatters default attributes
    protected $currentRouteName;
    protected $format;

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
    abstract public function format();

    /**
     * Format raw data to have hypermedia metadata in output
     *
     * @return array
     */
    abstract public function formatMetadata();

    /**
     * Format raw data to have hypermedia data in output
     *
     * @return array
     */
    abstract public function formatData();

    /**
     * Format raw data to have hypermedia links in output
     *
     * @return array
     */
    abstract public function formatLinks();

    /**
     * Give object type
     *
     * @return string
     */
    abstract public function getType();
}
