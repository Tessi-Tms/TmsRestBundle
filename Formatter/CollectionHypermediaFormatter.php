<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

class CollectionHypermediaFormatter extends HypermediaFormatter
{
    protected $criteria = null;
    protected $limit = null;
    protected $sort = null;
    protected $page = null;
    protected $offset = null;

    /**
     * Constructor
     */
    public function __construct($router, $tmsRestCriteriaBuilder, $serializer, $currentRouteName, $format)
    {
        $this->currentRouteName = $currentRouteName;
        $this->format = $format;
        parent::__construct($router, $tmsRestCriteriaBuilder, $serializer);
        
        // Initialize configuration by route
        $this->tmsRestCriteriaBuilder->guessPaginationByRoute($currentRouteName);
    }

    /**
     * {@inheritdoc }
     */
    public function format()
    {
        // Set default values if some parameters are missing
        $this->clean(array(
            'criteria' => $this->criteria,
            'limit'    => $this->limit,
            'sort'     => $this->sort,
            'page'     => $this->page,
            'offset'   => $this->offset
        ));

        $entities = $this
            ->tmsEntityManager
            ->findBy(
                $this->criteria,
                $this->sort,
                $this->limit,
                $this->offset
            )
        ;

        // #######################################
        // 
        //    TODO : OPTIMIZE COUNT FUNCTION
        // 
        // #######################################
        $totalCount = $this
            ->tmsEntityManager
            ->count($this->criteria)
        ;

        return array(
            'metadata' => $this->formatMetadata($totalCount),
            'data'     => $this->formatData($entities),
            'links'    => $this->formatLinks($totalCount)
        );
    }

    /**
     * Format metadata into a given layout for hypermedia
     *
     * @param int|null $totalCount
     * @return array
     */
    public function formatMetadata($totalCount)
    {
        return array(
            'type'          => $this->getClassNamespace(),
            'page'          => $this->page,
            'pageCount'     => $this->computePageCount($totalCount),
            'totalCount'    => $totalCount,
            'limit'         => $this->limit,
            'offset'        => $this->offset
        );
    }

    /**
     * Format data into a given layout for hypermedia
     *
     * @param array $entities
     * @return array
     */
    public function formatData($entities)
    {
        return $entities;
    }

    /**
     * Define the criteria according to the original value and configuration
     *
     * @param array $criteria
     * @return $this
     */
    public function setCriteria($criteria = null)
    {
        $this->criteria = $this
            ->tmsRestCriteriaBuilder
            ->defineCriteriaValue($criteria)
        ;
        
        return $this;
    }
    
    /**
     * Define the limit according to the original value and configuration
     *
     * @param array $limit
     * @return $this
     */
    public function setLimit($limit = null)
    {
        $this->limit = $this
            ->tmsRestCriteriaBuilder
            ->defineLimitValue($limit)
        ;
        
        return $this;
    }
    
    /**
     * Define the sort according to the original value and configuration
     *
     * @param array $sort
     * @return $this
     */
    public function setSort($sort = null)
    {
        $this->sort = $this
            ->tmsRestCriteriaBuilder
            ->defineSortValue($sort)
        ;
        
        return $this;
    }
    
    /**
     * Define the page according to the original value and configuration
     *
     * @param array $page
     * @return $this
     */
    public function setPage($page = null)
    {
        $this->page = $this
            ->tmsRestCriteriaBuilder
            ->definePageValue($page)
        ;
        
        return $this;
    }

    /**
     * Define the offset according to the original value and configuration
     *
     * @param array $offset
     * @return $this
     */
    public function setOffset($offset = null)
    {
        $this->offset = $this
            ->tmsRestCriteriaBuilder
            ->defineOffsetValue($offset)
        ;
        
        return $this;
    }

    /**
     * Define all params with configuration if some are not given
     *
     * @param array $parameters
     */
    public function clean(array $params)
    {
        foreach($params as $name => $value) {
            if(is_null($value)) {
                $defineMethod = sprintf("define%sValue", ucfirst($name));
                $this->$name = $this
                    ->tmsRestCriteriaBuilder
                    ->$defineMethod()
                ;
            }
        }
    }

    /**
     * Format links into a given layout for hypermedia
     *
     * @param int|null $totalCount
     * @return array
     */
    public function formatLinks($totalCount)
    {
        return array(
            'self' => array(
                'href' => $this->router->generate(
                    $this->currentRouteName,
                    array(
                        '_format' => $this->format,
                    ),
                    true
                )
            ),
            'next' => $this->generateNextLink($totalCount),
            'previous' => $this->generatePreviousLink()
        );
    }

    /**
     * Generate next link to navigate in hypermedia collection
     *
     * @param int|null $totalCount
     * @return string
     */
    public function generateNextLink($totalCount)
    {
        if ($this->page + 1 > ceil($totalCount / $this->limit)) {
            return '';
        }

        return $this->router->generate(
            $this->currentRouteName,
            array(
                '_format' => $this->format,
                'page'    => $this->page+1,
            ),
            true
        );
    }

    /**
     * Generate previous link to navigate in hypermedia collection
     *
     * @param int|null $totalCount
     * @return string
     */
    public function generatePreviousLink() {
        if ($this->page - 1 < 1) {
            return '';
        }

        return $this->router->generate(
            $this->currentRouteName,
            array(
                '_format' => $this->format,
                'page'    => $this->page-1,
            ),
            true
        );
    }

    /**
     * Compute the actual elements number of a page of a collection
     *
     * @param int|null $totalCount
     * @return int
     */
    public function computePageCount($totalCount)
    {
        if($this->offset > $totalCount) {
            return 0;
        } else {
            if($totalCount-$this->offset > $this->limit) {
                return $this->limit;
            } else {
               return $totalCount-$this->offset; 
            }
        }

        return 0;
    }
}
