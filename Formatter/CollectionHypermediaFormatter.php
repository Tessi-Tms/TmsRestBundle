<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

class CollectionHypermediaFormatter extends AbstractHypermediaFormatter
{
    protected $criteria = null;
    protected $limit = null;
    protected $sort = null;
    protected $page = null;
    protected $offset = null;
    
    protected $totalCount;
    protected $objects;

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

        // Retrieve objects according to a given criteria
        $this->objects = $this
            ->objectManager
            ->getRepository($this->objectNamespace)
            ->findBy(
                $this->criteria,
                $this->sort,
                $this->limit,
                $this->offset
            )
        ;

        // Count objects according to a given criteria
        $this->totalCount = $this->countObjects($this->criteria);
 
        return array(
            'metadata' => $this->formatMetadata(),
            'data'     => $this->formatData(),
            'links'    => $this->formatLinks()
        );
    }

    /**
     * Format metadata into a given layout for hypermedia
     *
     * @param int|null $totalCount
     * @return array
     */
    public function formatMetadata()
    {
        return array(
            'type'          => $this->getClassNamespace(),
            'page'          => $this->page,
            'pageCount'     => $this->computePageCount(),
            'totalCount'    => $this->totalCount,
            'limit'         => $this->limit,
            'offset'        => $this->offset
        );
    }

    /**
     * Format data into a given layout for hypermedia
     *
     * @param array $objects
     * @return array
     */
    public function formatData()
    {
        return $this->objects;
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
            ->criteriaBuilder
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
            ->criteriaBuilder
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
            ->criteriaBuilder
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
            ->criteriaBuilder
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
            ->criteriaBuilder
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
                    ->criteriaBuilder
                    ->$defineMethod()
                ;
            }
        }
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
                'href' => $this->router->generate(
                    $this->currentRouteName,
                    array(
                        '_format' => $this->format,
                    ),
                    true
                )
            ),
            'next' => $this->generateNextLink(),
            'previous' => $this->generatePreviousLink()
        );
    }

    /**
     * Generate next link to navigate in hypermedia collection
     *
     * @return string
     */
    public function generateNextLink()
    {
        if ($this->page + 1 > ceil($this->totalCount / $this->limit)) {
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
     * Compute the actual elements number of a page in a collection
     *
     * @return int
     */
    public function computePageCount()
    {
        if($this->offset > $this->totalCount) {
            return 0;
        } else {
            if($this->totalCount-$this->offset > $this->limit) {
                return $this->limit;
            } else {
               return $this->totalCount-$this->offset; 
            }
        }

        return 0;
    }

    /**
     * Prepare a query builder to count objects
     *
     * @param array $criteria
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function prepareQueryBuilderCount($criteria = null)
    {
        $qb = $this
            ->objectManager
            ->getRepository($this->objectNamespace)
            ->createQueryBuilder('object')
            ->select('COUNT(object.id)');

        if(is_null($criteria)) {
            return $qb;
        }

        foreach($criteria as $name => $value) {
            $qb->andWhere(sprintf('object.%s = %s', $name, $value));
        }

        return $qb;
    }

    /**
     * Count objects query
     *
     * @param array $criteria
     * @return \Doctrine\ORM\Query
     */
    public function prepareQueryCount($criteria = null)
    {
        return $this->prepareQueryBuilderCount($criteria)->getQuery();
    }

    /**
     * Count objects
     *
     * @param array $criteria
     * @return integer
     */
    public function countObjects($criteria = null)
    {
        try {
            return $this->prepareQueryCount($criteria)->getSingleScalarResult();
        } catch(\Exception $e) {
            return 0;
        }
    }
}
