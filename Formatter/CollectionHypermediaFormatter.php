<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

class CollectionHypermediaFormatter extends AbstractFormatter
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
        $this->tmsRestCriteriaBuilder->clean(
            $parameters,
            $route
        );

        $entities = $this
            ->tmsEntityManager
            ->findBy(
                $parameters['criteria'],
                array(
                    $parameters['sort']['field'] =>
                    $parameters['sort']['order']
                ),
                $parameters['limit'],
                $parameters['offset']
            )
        ;

        // #######################################
        // 
        //    TODO : OPTIMIZE COUNT FUNCTION
        // 
        // #######################################
        $totalCount = $this
            ->tmsEntityManager
            ->count($parameters['criteria'])
        ;

        return array(
            'metadata' => $this->formatMetadata(
                $totalCount,
                $parameters['limit'],
                $parameters['offset'],
                $parameters['page']
            ),
            'data'  => $this->formatData($entities),
            'links' => $this->formatLinks($route, $parameters['page'], $totalCount, $parameters['limit'])
        );
    }
    
    public function formatMetadata($totalCount, $limit, $offset, $page)
    {
        return array(
            'type'          => $this->tmsEntityManager->getEntityClass(),
            'page'          => $page,
            'pageCount'     => 
            $this->computePageCount(
                $totalCount,
                $offset,
                $limit
            ),
            'totalCount'    => $totalCount,
            'limit'         => $limit,
            'offset'        => $offset
        );
    }
    
    public function formatData($entities)
    {
        return $entities;
    }
    
    public function formatLinks($route, $page, $totalCount, $limit)
    {
        return array(
            'self' => array(
                'href' => $this->router->generate($route)
            ),
            'next' => $this->generateNextLink(
                $route,
                $page,
                $totalCount,
                $limit
            ),
            'previous' => $this->generatePreviousLink(
                $route,
                $page
            )
        );
    }
    
    public function generateNextLink($route, $currentPage, $totalCount, $limit)
    {
        if ($currentPage + 1 > ceil($totalCount / $limit)) {
            return '';
        }

        return $this
            ->router
            ->generate($route, array('page' => $currentPage+1))
        ;
    }

    public function generatePreviousLink($route, $currentPage) {
        if ($currentPage - 1 < 1) {
            return '';
        }

        return $this
            ->router
            ->generate($route, array('page' => $currentPage-1))
        ;
    }
    
    public function computePageCount($totalCount, $offset, $limit)
    {
        if($offset > $totalCount) {
            return 0;
        } else {
            if($totalCount-$offset > $limit) {
                return $limit;
            } else {
               return $totalCount-$offset; 
            }
        }
    }
}
