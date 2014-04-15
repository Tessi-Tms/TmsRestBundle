<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

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
    protected $tmsRestCriteriaBuilder;
    protected $tmsEntityManager;

    /**
     * Constructor
     * 
     */
    public function __construct($router, $tmsRestCriteriaBuilder, $serializer)
    {
        $this->router = $router;
        $this->tmsRestCriteriaBuilder = $tmsRestCriteriaBuilder;
        $this->serializer = $serializer;
    }

    /**
     * Dependency injection to set entity manager to the formatter
     *
     * @param EntityManager $tmsEntityManager
     * @return $this
     */
    public function setTmsEntityManager($tmsEntityManager)
    {
        $this->tmsEntityManager = $tmsEntityManager;

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
            $this->tmsRestCriteriaBuilder,
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
     * @param string $entityId
     * @return SingleHypermediaFormatter
     */
    public function buildSingleFormatter($currentRouteName, $format, $entityId)
    {
        return new SingleHypermediaFormatter(
            $this->router,
            $this->tmsRestCriteriaBuilder,
            $this->serializer,
            $currentRouteName,
            $format,
            $entityId
        );
    }

    /**
     * Give a class metadata collection thanks to the
     * entity manager and the entity class name
     *
     * @return ClassMetadataCollection
     */
    public function getClassMetadata()
    {
        return $this
            ->tmsEntityManager
            ->getEntityManager()
            ->getClassMetadata($this->tmsEntityManager->getEntityClass());
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
