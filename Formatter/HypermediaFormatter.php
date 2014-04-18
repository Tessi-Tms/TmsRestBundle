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

class HypermediaFormatter extends AbstractFormatter
{
    const SERIALIZER_CONTEXT_GROUP_SINGLE = 'details';
    const SERIALIZER_CONTEXT_GROUP_COLLECTION = 'list';

    // Formatters default attributes
    protected $currentRouteName;
    protected $format;

    // Services
    protected $serializer;
    protected $router;
    protected $criteriaBuilder;
    protected $objectManager;
    protected $objectNamespace;

    /**
     * Constructor
     * 
     */
    public function __construct($router, $criteriaBuilder, $serializer)
    {
        $this->router = $router;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->serializer = $serializer;
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
     * Instantiate a CollectionHypermediaFormatter
     *
     * @param string $currentRouteName
     * @param string $format
     * @return CollectionHypermediaFormatter
     */
    public function buildCollectionFormatter($currentRouteName, $format)
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
    public function buildSingleFormatter($currentRouteName, $format, $objectId)
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
    public function format() {}
}
