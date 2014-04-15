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

class SingleHypermediaFormatter extends HypermediaFormatter
{
    protected $entityId;
    protected $entity;
    protected $embedded;

    /**
     * Constructor
     */
    public function __construct($router, $tmsRestCriteriaBuilder, $serializer, $currentRouteName, $format, $entityId)
    {
        $this->currentRouteName = $currentRouteName;
        $this->format = $format;
        $this->entityId = $entityId;
        parent::__construct($router, $tmsRestCriteriaBuilder, $serializer);

        // Initialize configuration by route
        $this->tmsRestCriteriaBuilder->guessPaginationByRoute($currentRouteName);
    }

    /**
     * Dependency injection to set entity manager to the formatter
     * Moreoever, retrieve the entity thanks to the given ID and throw
     * an exception if not found
     *
     * @param EntityManager $tmsEntityManager
     * @return $this
     */
    public function setTmsEntityManager($tmsEntityManager)
    {
        $this->entity = $tmsEntityManager->findOneById($this->entityId);
        if (!$this->entity) {
            throw new NotFoundHttpException("Entity not found.");
        }

        return parent::setTmsEntityManager($tmsEntityManager);
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
        return $this->entity;
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
                    'id'      => $this->entity->getId(),
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
     * Add an embedded element to a single hypermedia entity
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
                    'type' => $this->getEmbeddedType($embeddedName)
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
                                'id'      => $this->entity->getId(),
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
     * mapped by the single entity
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
     * Retrieve embedded entities for a given embedded name
     * 
     * @param string $embeddedName
     * @return Collection
     */
    public function getEmbeddedData($embeddedName)
    {
        $retrieveEmbeddedMethod = sprintf("get%s", ucfirst($embeddedName));
        return $this->entity->$retrieveEmbeddedMethod();
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
     * @param string $embeddedEntities
     * @return array
     */
    public function formatEmbeddedData($embeddedSingleRoute, $embeddedEntities)
    {
        $formattedEntities = array();
        
        foreach($embeddedEntities as $entity) {
            array_push($formattedEntities, array(
                'data' => $entity,
                'links' => array(
                    'self' => array(
                        'href' => $this->router->generate(
                            $embeddedSingleRoute,
                            array(
                                '_format' => $this->format,
                                'id'      => $entity->getId(),
                            ),
                            true
                        )
                    )
                ),
                'metadata' => array()
            ));
        }

        return $formattedEntities;
    }

    /**
     * Give embedded entity namespace
     *
     * @return string
     */
    public function getEmbeddedType($embeddedName)
    {
        return $this
            ->getClassMetadata()
            ->associationMappings[$embeddedName]['targetEntity'];
    }
}
