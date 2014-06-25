<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Factory;

use Tms\Bundle\RestBundle\Formatter\DoctrineCollectionHypermediaFormatter;
use Tms\Bundle\RestBundle\Formatter\DoctrineItemHypermediaFormatter;
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
     * Instantiate a DoctrineCollectionHypermediaFormatter
     *
     * @param string $currentRouteName
     * @param string $format
     * 
     * @return DoctrineCollectionHypermediaFormatter
     */
    public function buildDoctrineCollectionHypermediaFormatter($currentRouteName, $format)
    {
        return new DoctrineCollectionHypermediaFormatter(
            $this->router,
            $this->criteriaBuilder,
            $this->serializer,
            $currentRouteName,
            $format
        );
    }

    /**
     * Instantiate a DoctrineItemHypermediaFormatter
     *
     * @param string $currentRouteName
     * @param string $format
     * @param mixed  $objectPKValue
     * @param mixed  $objectPK
     * 
     * @return DoctrineItemHypermediaFormatter
     */
    public function buildDoctrineItemHypermediaFormatter($currentRouteName, $format, $objectPKValue, $objectPK = 'id')
    {
        return new DoctrineItemHypermediaFormatter(
            $this->router,
            $this->criteriaBuilder,
            $this->serializer,
            $currentRouteName,
            $format,
            $objectPKValue,
            $objectPK
        );
    }
}
