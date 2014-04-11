<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

namespace Tms\Bundle\RestBundle\Formatter;

class SingleHypermediaFormatter extends AbstractFormatter
{
    protected $router;
    protected $tmsRestCriteriaBuilder;
    protected $tmsEntityManager;

    public function __construct($router, $tmsRestCriteriaBuilder)
    {
        $this->router = $router;
        $this->tmsRestCriteriaBuilder = $tmsRestCriteriaBuilder;
    }

    public function setTmsEntityManager($tmsEntityManager)
    {
        $this->tmsEntityManager = $tmsEntityManager;

        return $this;
    }

    public function format($parameters, $route)
    {
        $entity = $this->tmsEntityManager->findOneById($parameters['id']);
        if (!$entity) {
            throw new NotFoundHttpException("Entity not found.");
        }
        
        return array(
            'metadata'  => $this->formatMetadata(),
            'data'      => $this->formatData($entity),
            'links'     => $this->formatLinks($route, $parameters['id']),
            'embedded'  => array()
//                'products' => array(array(
//                    'metadata' => array(
//                        'type' => get_class($products[0])
//                    ),
//                    'data' => $this->serializeEmbeddedEntities('api_products_get_product', $products),
//                    'links' => array(
//                        'self' => array(
//                            'href' => $this->get('router')
//                                        ->generate(
//                                            'api_offers_get_offer_products',
//                                            array('id' => $entity->getId())
//                                        )
//                        )
//                    ),
//                )
        );
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
                'href' => $this->get('router')
                    ->generate(
                        $route,
                        array('id' => $id)
                    )
            ),
        );
    }
}
