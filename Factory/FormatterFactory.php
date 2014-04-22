<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Factory;

use Tms\Bundle\RestBundle\Formatter\CollectionHypermediaFormatter;
use Tms\Bundle\RestBundle\Formatter\SingleHypermediaFormatter;
use Tms\Bundle\RestBundle\Criteria\CriteriaBuilder;
use Symfony\Component\Routing\Router;
use JMS\Serializer\Serializer;

class FormatterFactory
{
    // Services
    protected $serializer;
    protected $router;
    protected $criteriaBuilder;

    /**
     * Constructor
     * 
     */
    public function __construct(Router $router, CriteriaBuilder $criteriaBuilder, Serializer $serializer)
    {
        $this->router = $router;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->serializer = $serializer;
    }

    /**
     * Instantiate a CollectionHypermediaFormatter
     *
     * @param string $currentRouteName
     * @param string $format
     * @return CollectionHypermediaFormatter
     */
    public function buildCollectionHypermediaFormatter($currentRouteName, $format)
    {
        return new CollectionHypermediaFormatter(
            $this->router,
            $this->criteriaBuilder,
            $this->serializer,
            $currentRouteName,
            $format
        );
    }

    /**
     * Instantiate a SingleHypermediaFormatter
     *
     * @param string $currentRouteName
     * @param string $format
     * @param string $objectId
     * @return SingleHypermediaFormatter
     */
    public function buildSingleHypermediaFormatter($currentRouteName, $format, $objectId)
    {
        return new SingleHypermediaFormatter(
            $this->router,
            $this->criteriaBuilder,
            $this->serializer,
            $currentRouteName,
            $format,
            $objectId
        );
    }
    
}
