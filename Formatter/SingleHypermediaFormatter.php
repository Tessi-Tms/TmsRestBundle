<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\Common\Persistence\ObjectManager;

class SingleHypermediaFormatter extends HypermediaFormatter
{
    protected $objectId = null;
    protected $object = null;
    protected $embedded = null;

    /**
     * Constructor
     */
    public function __construct($router, $criteriaBuilder, $serializer, $currentRouteName, $format, $objectId)
    {
        $this->currentRouteName = $currentRouteName;
        $this->format = $format;
        $this->objectId = $objectId;

        parent::__construct($router, $criteriaBuilder, $serializer);

        // Initialize configuration by route
        $this->criteriaBuilder->guessConfigurationByRoute($currentRouteName);
    }

    /**
     * Dependency injection to set object manager to the formatter
     *
     * @param ObjectManager $objectManager
     * @param string $objectNamespace
     */
    public function setObjectManager(ObjectManager $objectManager, $objectNamespace)
    {
        $this->object = $objectManager
            ->getRepository($objectNamespace)
            ->findOneById($this->objectId);
        if (!$this->object) {
            throw new NotFoundHttpException();
        }

        return parent::setObjectManager($objectManager, $objectNamespace);
    }

    /**
     * {@inheritdoc }
     */
    public function format()
    {
        return array(
            'metadata' => $this->formatMetadata(),
            'data'     => $this->formatData(),
            'links'    => $this->formatLinks(),
            'embedded' => $this->formatEmbedded()
        );
    }

    /**
     * Format metadata into a given layout for hypermedia
     *
     * @return array
     */
    public function formatMetadata()
    {
        return array(
            'type' => $this->getClassNamespace(),
        );
    }

    /**
     * Format data into a given layout for hypermedia
     *
     * @return array
     */
    public function formatData()
    {
        return $this->object;
    }

    /**
     * Format links into a given layout for hypermedia
     *
     * @return array
     */
    public function formatLinks()
    {
        return array('self' => 
            $this->router->generate(
                $this->currentRouteName,
                array(
                    '_format' => $this->format,
                    'id'      => $this->object->getId(),
                ),
                true
            )
        );
    }

    /**
     * Format embedded data of 1st depth into a given layout for hypermedia
     * array(
     *      'data'     => X,
     *      'metadata' => X
     *      'links'    => X,
     *      'embedded' => $this->formatEmbedded()
     * )
     *
     * @return array
     */
    public function formatEmbedded()
    {
        return $this->embedded;
    }

    /**
     * Add an embedded element to a single hypermedia object
     * You can chain this method easily
     *
     * @param string $embeddedName
     * @param string $embeddedSingleRoute
     * @param string $embeddedCollectionRoute
     * @return $this
     */
    public function addEmbedded($embeddedName, $embeddedSingleRoute, $embeddedCollectionRoute)
    {
        if($this->isEmbeddedMappedBySingleEntity($embeddedName)) {
            $this->embedded[$embeddedName] = array(
                'metadata' => array(
                    'type' => $this->getEmbeddedNamespace($embeddedName)
                ),
                'data'  => $this->formatEmbeddedData(
                    $embeddedSingleRoute,
                    $this->getEmbeddedData($embeddedName)
                ),
                'links' => array(
                    'self' => array(
                        'href' => $this->router->generate(
                            $embeddedCollectionRoute,
                            array(
                                '_format' => $this->format,
                                'id'      => $this->object->getId(),
                            ),
                            true
                        )
                    )
                )
            );
        }
        
        return $this;
    }

    /**
     * Check if a requested embedded element is actually
     * mapped by the single object
     *
     * @param string $embeddedName
     * @return boolean
     */
    public function isEmbeddedMappedBySingleEntity($embeddedName)
    {
        return array_key_exists(
            $embeddedName,
            $this->getClassMetadata()->associationMappings
        );
    }

    /**
     * Guess retrieve embedded data method
     * Retrieve embedded objects for a given embedded name
     * Example : $offer->getProducts()
     * 
     * @param string $embeddedName
     * @return Collection
     */
    public function getEmbeddedData($embeddedName)
    {
        $retrieveEmbeddedMethod = sprintf("get%s", ucfirst($embeddedName));
        return $this->object->$retrieveEmbeddedMethod();
    }

    /**
     * Format embedded data of 2nd depth into a given layout for hypermedia
     * array(
     *      'data'     => X,
     *      'metadata' => X
     *      'links'    => X,
     *      'embedded' => array(
     *          'data'     => $this->formatEmbeddedData(X, X),
     *          'metadata' => X
     *          'links'    => X,
     *      )
     * )
     * 
     * @param string $embeddedSingleRoute
     * @param string $embeddedObjects
     * @return array
     */
    public function formatEmbeddedData($embeddedSingleRoute, $embeddedObjects)
    {
        $formattedObjects = array();
        
        foreach($embeddedObjects as $object) {
            array_push($formattedObjects, array(
                'data' => $object,
                'links' => array(
                    'self' => array(
                        'href' => $this->router->generate(
                            $embeddedSingleRoute,
                            array(
                                '_format' => $this->format,
                                'id'      => $object->getId(),
                            ),
                            true
                        )
                    )
                ),
                'metadata' => array()
            ));
        }

        return $formattedObjects;
    }

    /**
     * Give embedded object namespace
     *
     * @return string
     */
    public function getEmbeddedNamespace($embeddedName)
    {
        return $this
            ->getClassMetadata()
            ->associationMappings[$embeddedName]['targetEntity'];
    }
}
