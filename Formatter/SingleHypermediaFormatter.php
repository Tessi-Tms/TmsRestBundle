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
use Tms\Bundle\RestBundle\Criteria\CriteriaBuilder;
use Symfony\Component\Routing\Router;
use JMS\Serializer\Serializer;

class SingleHypermediaFormatter extends AbstractHypermediaFormatter
{
    protected $objectPKValue = null;
    protected $object = null;
    protected $embedded = null;

    /**
     * Constructor
     * 
     */
    public function __construct(Router $router, CriteriaBuilder $criteriaBuilder, Serializer $serializer, $currentRouteName, $format, $objectPKValue)
    {
        $this->objectPKValue = $objectPKValue;

        parent::__construct($router, $criteriaBuilder, $serializer, $currentRouteName, $format);
    }

    /**
     * Find object thanks to params['primaryKey'] and params['primaryValue']
     *
     * @return Object
     */
    public function retrieveObject()
    {
        if(!$this->object) {
            $retrieveObjectMethod = sprintf("findOneBy%s", ucfirst($this->getClassIdentifier()));
            $object = $this->objectManager
                ->getRepository($this->objectNamespace)
                ->$retrieveObjectMethod($this->objectPKValue);

            if (!$object) {
                throw new NotFoundHttpException();
            }
            
            $this->object = $object;
            
        }
    }

    /**
     * {@inheritdoc }
     */
    public function format()
    {
        $this->retrieveObject();

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
                    '_format'                   => $this->format,
                    $this->getClassIdentifier() => $this->objectPKValue,
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
        $this->retrieveObject();

        if($this->isEmbeddedMappedBySingleEntity($embeddedName)) {
            $this->embedded[$embeddedName] = array(
                'metadata' => array(
                    'type' => $this->getEmbeddedNamespace($embeddedName)
                ),
                'data'  => $this->formatEmbeddedData(
                    $embeddedName,
                    $embeddedSingleRoute,
                    $this->getEmbeddedData($embeddedName)
                ),
                'links' => array(
                    'self' => array(
                        'href' => $this->router->generate(
                            $embeddedCollectionRoute,
                            array(
                                '_format' => $this->format,
                                $this->getClassIdentifier() => $this->objectPKValue,
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
     * @param string $embeddedName
     * @param string $embeddedSingleRoute
     * @param string $embeddedObjects
     * @return array
     */
    public function formatEmbeddedData($embeddedName, $embeddedSingleRoute, $embeddedObjects)
    {
        $formattedObjects = array();
        
        foreach($embeddedObjects as $object) {
            $getMethod = sprintf("get%s", ucfirst($this->getClassIdentifier()));
        
            array_push($formattedObjects, array(
                'data' => $object,
                'links' => array(
                    'self' => array(
                        'href' => $this->router->generate(
                            $embeddedSingleRoute,
                            array(
                                '_format' => $this->format,
                                $this->getClassIdentifier($this->getEmbeddedNamespace($embeddedName)) => $object->$getMethod(),
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
