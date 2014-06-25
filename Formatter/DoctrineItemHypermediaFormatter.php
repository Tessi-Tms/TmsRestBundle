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
use Tms\Bundle\RestBundle\Criteria\CriteriaBuilder;
use Symfony\Component\Routing\Router;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;

class DoctrineItemHypermediaFormatter extends AbstractDoctrineHypermediaFormatter
{
    protected $objectPK = 'id';
    protected $objectPKValue = null;
    protected $object = null;
    protected $embeddeds = null;

    /**
     * Constructor
     */
    public function __construct(Router $router, CriteriaBuilder $criteriaBuilder, Serializer $serializer, $currentRouteName, $format, $objectPKValue, $objectPK = 'id')
    {
        $this->objectPKValue = $objectPKValue;
        $this->objectPK = $objectPK;

        parent::__construct($router, $criteriaBuilder, $serializer, $currentRouteName, $format);
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
        return array(
            'self' => array(
                'rel' => 'self',
                'href' => $this->generateSelfLink(
                    $this->currentRouteName,
                    $this->object
                )
            ),
            'embeddeds' => $this->formatEmbeddeds()
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
    public function formatEmbeddeds()
    {
        return $this->embeddeds;
    }

    /**
     * Find single object from repository with objectPKValue
     *
     * @return Object
     */
    public function getObjectsFromRepository()
    {
        if(!$this->object) {
            $findOneByMethod = sprintf("findOneBy%s", ucfirst($this->objectPK));
            $object = $this->objectManager
                ->getRepository($this->objectNamespace)
                ->$findOneByMethod($this->objectPKValue);

            if (!$object) {
                throw new NotFoundHttpException();
            }

            $this->object = $object;
        }

        return $this;
    }

    /**
     * Add an embedded element to a single hypermedia object
     * You can chain this method easily
     *
     * @param string $embeddedName
     * @param string $embeddedSingleRoute
     * @param string $embeddedCollectionRoute
     * 
     * @return $this
     */
    public function addEmbedded($embeddedName, $embeddedSingleRoute, $embeddedCollectionRoute)
    {
        $this->getObjectsFromRepository();

        if($this->isEmbeddedMappedBySingleEntity($embeddedName)) {
            $this->embeddeds[$embeddedName] = array(
                'rel'   => 'embedded',
                'href'  => $this->generateSelfLink(
                    $embeddedCollectionRoute,
                    $this->object
                )
            );
        }

        return $this;
    }

    /**
     * Generate the self link for a single object
     * 
     * @param string $routeName
     * @param Object $object
     * 
     * @return Collection
     */
    public function generateSelfLink($routeName, $object)
    {
        $classIdentifier = $this->getClassIdentifier(get_class($object));
        $getMethod = sprintf("get%s", ucfirst($classIdentifier));

        return $this->router->generate(
            $routeName,
            array(
                '_format' => $this->format,
                $classIdentifier => $object->$getMethod(),
            ),
            true
        );
    }

    /**
     * Check if a requested embedded element is actually
     * mapped by the single object
     *
     * @param string $embeddedName
     * 
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
     * {@inheritdoc }
     */
    public function getSerializerContextGroup()
    {
        return AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_ITEM;
    }
}
