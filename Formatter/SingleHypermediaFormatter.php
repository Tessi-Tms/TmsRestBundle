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
use Tms\Bundle\RestBundle\Formatter\AbstractFormatter;

class SingleHypermediaFormatter extends AbstractFormatter
{
    protected $router;
    protected $tmsRestCriteriaBuilder;
    protected $tmsEntityManager;
    protected $associations;

    public function __construct($router, $tmsRestCriteriaBuilder, $serializer)
    {
        parent::__construct($router, $tmsRestCriteriaBuilder, $serializer);
    }

    public function setTmsEntityManager($tmsEntityManager)
    {
        $this->tmsEntityManager = $tmsEntityManager;

        return $this;
    }

    public function format($parameters, $route, $format)
    {
        $entity = $this->tmsEntityManager->findOneById($parameters['singleId']);
        if (!$entity) {
            throw new NotFoundHttpException("Entity not found.");
        }

        $data = array();
        $data['metadata'] = $this->formatMetadata();
        $data['data']     = $this->formatData($entity);
        $data['links']    = $this->formatLinks($route, $format, $entity->getId());

        if(isset($parameters['embedded'])) {
            $this->guessEntityAssociations();

            foreach($parameters['embedded'] as $embeddedName => $embeddedRoutes) {
                if(array_key_exists($embeddedName, $this->associations)) {
                    $data['embedded'][$embeddedName] = $this->addEmbedded(
                        $entity,
                        $embeddedName,
                        $embeddedRoutes['singleRoute'],
                        $embeddedRoutes['collectionRoute'],
                        $format
                    );
                }
            }
        }
        
        return $data;
    }
    
    public function guessEntityAssociations()
    {
        $this->associations = $this
            ->tmsEntityManager
            ->getEntityManager()
            ->getClassMetadata($this->tmsEntityManager->getEntityClass())
            ->associationMappings;
    }

    public function addEmbedded($singleEntity, $embeddedName, $embeddedSingleRoute, $embeddedCollectionRoute, $format)
    {
        $retrieveEmbeddedMethod = $this->guessRetrieveEmbeddedMethod($embeddedName);
        $embeddedEntities = $singleEntity->$retrieveEmbeddedMethod();

        return array(
            'metadata' => array(
                'type' => get_class($embeddedEntities[0])
            ),
            'data'  => $this->formatEmbeddedData($embeddedSingleRoute, $embeddedEntities, $format),
            'links' => array(
                'self' => array(
                    'href' => $this->router->generate(
                        $embeddedCollectionRoute,
                        array(
                            '_format' => $format,
                            'id' => $singleEntity->getId(),
                        ),
                        true
                    )
                )
            )
        );
    }

    public function guessRetrieveEmbeddedMethod($embeddedName)
    {
        return sprintf("get%s", ucfirst($embeddedName));
    }

    public function formatEmbeddedData($embeddedSingleRoute, $embeddedEntities, $format)
    {
        $formattedEntities = array();
        
        foreach($embeddedEntities as $entity) {
            array_push($formattedEntities, array(
                'data' => $this
                    ->serializer
                    ->serialize(
                        $entity, 
                        'json', 
                        \JMS\Serializer\SerializationContext::create()->setGroups(AbstractFormatter::SERIALIZER_CONTEXT_GROUP_COLLECTION)
                    ),
                'links' => array(
                    'self' => array(
                        'href' => $this->router->generate(
                            $embeddedSingleRoute,
                            array(
                                '_format' => $format,
                                'id' => $entity->getId(),
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

    public function formatMetadata()
    {
        return array(
            'type' => $this->tmsEntityManager->getEntityClass(),
        );
    }

    public function formatData($entity)
    {
        return $entity;
    }

    public function formatLinks($routeName, $format, $id)
    {
        return array('self' => 
            $this->router->generate(
                $routeName,
                array(
                    '_format' => $format,
                    'id' => $id,
                ),
                true
            )
        );
    }
}
