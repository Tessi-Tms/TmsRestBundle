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
    protected $tmsOperationManagerOffer;

    public function __construct($router, $tmsRestCriteriaBuilder, $tmsOperationManagerOffer)
    {
        $this->router = $router;
        $this->tmsRestCriteriaBuilder = $tmsRestCriteriaBuilder;
        $this->tmsOperationManagerOffer = $tmsOperationManagerOffer;
    }

    public function format(
        $criteria,
        $limit,
        $route,
        $orderbydirection,
        $orderbycolumn,
        $offset,
        $page
    )
    {
        $this->restCriteriaBuilder->clean(
            $criteria,
            $limit,
            $route,
            $orderbydirection,
            $orderbycolumn
        );

        $entities = $this
            ->tmsOperationManagerOffer
            ->findBy(
                $criteria,
                array($orderbycolumn => $orderbydirection),
                $limit,
                $offset
            )
        ;

        // TODO : OPTIMIZE COUNT FUNCTIONS!
        $pageCount = count($entities);
        $totalCount = $this
            ->tmsOperationManagerOffer
            ->count($criteria)
        ;

        return array(
            'metadata' => $this->formatMetadata(array(
                $entities,
                $pageCount,
                $totalCount,
                $limit,
                $offset,
                $page
            )),
            'data'  => $this->formatData($entities),
            'links' => $this->formatLinks($route, $page, $totalCount, $limit)
        );
        
    }
    
    public function formatMetadata($entities, $pageCount, $totalCount, $limit, $offset, $page)
    {
        return array(
            'type' => get_class($entities[0]),
            'page' => $page,
            'pageCount'  => $pageCount,
            'totalCount' => $totalCount,
            'limit'  => $limit,
            'offset' => $offset
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
            'next' => $this->generateListNextLink(
                $route,
                $page,
                $totalCount,
                $limit
            ),
            'previous' => $this->generateListPreviousLink(
                $route,
                $page
            )
        );
    }
    
    public function generateListNextLink($route, $currentPage, $totalCount, $limit)
    {
        if ($currentPage + 1 > ceil($totalCount / $limit)) {
            return '';
        }

        return $this
            ->router
            ->generate(
                $route,
                array('page' => $currentPage+1)
            )
        ;
    }

    public function generateListPreviousLink($route, $currentPage) {
        if ($currentPage - 1 < 1) {
            return '';
        }

        return $this
            ->router
            ->generate(
                $route,
                array('page' => $currentPage-1)
            )
        ;
    }
}
