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

class SingleHypermediaFormatter extends AbstractFormatter
{
    protected $router;
    protected $tmsRestCriteriaBuilder;
    protected $tmsEntityManager;
    protected $associations;

    public function __construct($router, $tmsRestCriteriaBuilder, $serializer)
    {
        $this->router = $router;
        $this->tmsRestCriteriaBuilder = $tmsRestCriteriaBuilder;
        parent::__construct($serializer);
    }

    public function setTmsEntityManager($tmsEntityManager)
    {
        $this->tmsEntityManager = $tmsEntityManager;

        return $this;
    }

    public function format($parameters, $route)
    {
        $entity = $this->tmsEntityManager->findOneById($parameters['singleId']);
        if (!$entity) {
            throw new NotFoundHttpException("Entity not found.");
        }

        $data = array();
        $data['metadata'] = $this->formatMetadata();
        $data['data']     = $this->formatData($entity);
        $data['links']    = $this->formatLinks($route, $entity->getId());

        if(isset($parameters['embedded'])) {
            $this->guessEntityAssociations();

            foreach($parameters['embedded'] as $embeddedName => $embeddedRoutes) {
                $data['embedded'][$embeddedName] = $this->addEmbedded(
                    $entity,
                    $embeddedName,
                    $embeddedRoutes['singleRoute'],
                    $embeddedRoutes['collectionRoute']
                );
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

    public function addEmbedded($singleEntity, $embeddedName, $embeddedSingleRoute, $embeddedCollectionRoute)
    {
        if(array_key_exists($embeddedName, $this->associations)) {
            $retrieveEmbeddedMethod = $this->guessRetrieveEmbeddedMethod($embeddedName);
            $embeddedEntities = $singleEntity->$retrieveEmbeddedMethod();

            return array(
                'metadata' => array(
                    'type' => get_class($embeddedEntities[0])
                ),
                'data'  => $this->formatEmbedded($embeddedSingleRoute, $embeddedEntities),
                'links' => array(
                    'self' => array(
                        'href' => $this
                            ->router
                            ->generate(
                                $embeddedCollectionRoute,
                                array('id' => $singleEntity->getId())
                            )
                    )
                )
            );
        }
    }
    
    public function guessRetrieveEmbeddedMethod($embeddedName)
    {
        return sprintf("get%s", ucfirst($embeddedName));
    }

    public function formatEmbedded($embeddedSingleRoute, $embeddedEntities)
    {
        $formattedEntities = array();
        
        foreach($embeddedEntities as $entity) {
            array_push($formattedEntities, array(
                'data' => $this
                    ->serializer
                    ->serialize(
                        $entity, 
                        'json', 
                        \JMS\Serializer\SerializationContext::create()->setGroups(array('list'))
                    ),
                'links' => array(
                    'self' => array(
                        'href' => $this
                            ->router
                            ->generate(
                                $embeddedSingleRoute,
                                array('id' => $entity->getId())
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

    public function formatLinks($route, $id)
    {
        return array(
            'self' => array(
                'href' => $this->router
                    ->generate(
                        $route,
                        array('id' => $id)
                    )
            ),
        );
    }
}
