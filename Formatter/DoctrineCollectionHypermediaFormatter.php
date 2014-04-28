<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

class DoctrineCollectionHypermediaFormatter extends AbstractDoctrineHypermediaFormatter
{
    // Query params
    protected $criteria = null;
    protected $limit = null;
    protected $sort = null;
    protected $page = null;
    protected $offset = null;
    protected $totalCount = null;

    protected $objects;
    protected $itemRoutes = null;
    
    /**
     * {@inheritdoc }
     */
    public function format()
    {
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
        return array_merge(
            array(
                'type'          => $this->getType(),
                'page'          => $this->page,
                'pageCount'     => $this->computePageCount(),
                'totalCount'    => $this->totalCount,
                'limit'         => $this->limit,
                'offset'        => $this->offset
            ),
            $this->criteria
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
        $data = array();
        foreach($this->objects as $object) {
            
            $data[] = array(
                'metadata' => array(
                    'type' => $this->getType()
                ),
                'data'  => $object,
                'links' => array(
                    'self' => array(
                        'href' => $this->generateItemLink($object)
                    )
                )
            );
            
        }
        return $data;
    }

    /**
     * Retrieve objects from repository
     *
     */
    public function getObjectsFromRepository()
    {
        // Set default values if some parameters are missing
        $this->clean(array(
            'criteria' => $this->criteria,
            'limit'    => $this->limit,
            'sort'     => $this->sort,
            'page'     => $this->page,
            'offset'   => $this->offset
        ));

        // Count objects according to a given criteria
        $this->totalCount = $this->countObjects($this->criteria);

        // Retrieve objects according to a given criteria
        $this->objects = $this
            ->objectManager
            ->getRepository($this->objectNamespace)
            ->findBy(
                $this->criteria,
                $this->sort,
                $this->limit,
                $this->computeOffsetWithPage()
            )
        ;

        return $this;
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
     * @param integer $limit
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
     * @param integer $page
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
     * @param integer $offset
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
                    array_merge(
                        array(
                            '_format'   => $this->format,
                            'page'      => $this->page,
                            'sort'      => $this->sort,
                            'limit'     => $this->limit,
                            'offset'    => $this->offset
                        ),
                        $this->criteria
                    ),
                    true
                )
            ),
            'next'      => $this->generateNextLink(),
            'previous'  => $this->generatePreviousLink()
        );
    }

    /**
     * Generate an item link
     *
     * @param mixed $object
     * @return url
     */
    public function generateItemLink($object)
    {
        $itemNamespace = $this->getClassNamespace(get_class($object));
        $getMethod = sprintf("get%s", ucfirst($this
            ->getClassIdentifier($itemNamespace)
        ));

        if(!$this->itemRoutes) {
            return sprintf("%s/%s.%s",
                $this->router->generate($this->currentRouteName, array(), true),
                $object->$getMethod(),
                $this->format
            );
        } else {
            return $this->router->generate(
                $this->itemRoutes[$this->getCleanedObjectName($itemNamespace)],
                array(
                    '_format' => $this->format,
                    $this->getClassIdentifier() => $object->$getMethod()
                ),
                true
            );
        }
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
                '_format'   => $this->format,
                'page'      => $this->page+1,
                'criteria'  => $this->criteria,
                'sort'      => $this->sort,
                'limit'     => $this->limit,
                'offset'    => $this->offset
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
                '_format'   => $this->format,
                'page'      => $this->page-1,
                'criteria'  => $this->criteria,
                'sort'      => $this->sort,
                'limit'     => $this->limit,
                'offset'    => $this->offset
            ),
            true
        );
    }

    /**
     * Compute the actual elements number of a page in a collection
     *
     * @return integer
     */
    public function computePageCount()
    {
        if($this->computeOffsetWithPage() > $this->totalCount) {
            return 0;
        } else {
            if($this->totalCount-$this->computeOffsetWithPage() > $this->limit) {
                return $this->limit;
            } else {
               return $this->totalCount-$this->computeOffsetWithPage(); 
            }
        }

        return 0;
    }

    /**
     * Compute the offset according to the page number
     *
     * @return integer
     */
    public function computeOffsetWithPage()
    {
        return $this->offset+($this->page-1)*$this->limit;
    }

    /**
     * Add a new route associated to an item namespace
     *
     * @param string $itemNamespace
     * @param string $itemRoute
     * 
     * @return $this
     */
    public function addItemRoute($itemNamespace, $itemRoute)
    {
        $this->itemRoutes[$this->getCleanedObjectName($itemNamespace)] = $itemRoute;
        
        return $this;
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
            $qb->andWhere(sprintf('object.%s = :%s', $name, $name));
            $qb->setParameter($name, $value);
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
