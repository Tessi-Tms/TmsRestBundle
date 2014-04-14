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
    public function __construct($router, $tmsRestCriteriaBuilder, $serializer)
    {
        parent::__construct($router, $tmsRestCriteriaBuilder, $serializer);
    }

    public function setTmsEntityManager($tmsEntityManager)
    {
        $this->tmsEntityManager = $tmsEntityManager;

        return $this;
    }
    
    public function format($parameters, $routeName, $format)
    {
        $this->tmsRestCriteriaBuilder->clean(
            $parameters,
            $routeName
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
            'links' => $this->formatLinks(
                $routeName,
                $format,
                $parameters['page'],
                $totalCount,
                $parameters['limit']
            )
        );
    }

    public function formatMetadata($totalCount, $limit, $offset, $page)
    {
        return array(
            'type'          => $this->tmsEntityManager->getEntityClass(),
            'page'          => $page,
            'pageCount'     => $this->computePageCount(
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
    
    public function formatLinks($routeName, $format, $page, $totalCount, $limit)
    {
        return array(
            'self' => array(
                'href' => $this->router->generate(
                    $routeName,
                    array(
                        '_format' => $format,
                    ),
                    true
                )
            ),
            'next' => $this->generateNextLink(
                $routeName,
                $format,
                $page,
                $totalCount,
                $limit
            ),
            'previous' => $this->generatePreviousLink(
                $routeName,
                $format,
                $page
            )
        );
    }
    
    public function generateNextLink($routeName, $format, $currentPage, $totalCount, $limit)
    {
        if ($currentPage + 1 > ceil($totalCount / $limit)) {
            return '';
        }

        return $this->router->generate(
            $routeName,
            array(
                '_format' => $format,
                'page'=> $currentPage+1,
            ),
            true
        );
    }

    public function generatePreviousLink($routeName, $format, $currentPage) {
        if ($currentPage - 1 < 1) {
            return '';
        }

        return $this->router->generate(
            $routeName,
            array(
                '_format' => $format,
                'page'=> $currentPage-1,
            ),
            true
        );
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
