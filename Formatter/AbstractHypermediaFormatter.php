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
    const SERIALIZER_CONTEXT_GROUP_SINGLE = 'details';
    const SERIALIZER_CONTEXT_GROUP_COLLECTION = 'list';

    // Services
    protected $serializer;
    protected $router;
    protected $criteriaBuilder;
    protected $objectManager;
    protected $objectNamespace;

    // Formatters default attributes
    protected $currentRouteName;
    protected $format;

    /**
     * Constructor
     * 
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
     * Dependency injection to set object manager to the formatter
     *
     * @param ObjectManager $objectManager
     * @param string $objectNamespace
     * @return array
     */
    public function setObjectManager(ObjectManager $objectManager, $objectNamespace)
    {
        $this->objectManager = $objectManager;
        $this->objectNamespace = $objectNamespace;

        return $this;
    }

    /**
     * Give a class metadata collection thanks to the
     * object manager and the object class namespace
     *
     * @return ClassMetadataCollection
     */
    public function getClassMetadata()
    {
        return $this
            ->objectManager
            ->getClassMetadata($this->objectNamespace);
    }

    /**
     * Give a class namespace
     *
     * @return string
     */
    public function getClassNamespace()
    {
        return $this->getClassMetadata()->getName();
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
}
