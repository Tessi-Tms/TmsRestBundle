<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

use Doctrine\ORM\QueryBuilder;

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
        $this->getObjectsFromRepository();

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
            $this->cleanCriteriaForLinks()
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
                        'rel'  => 'self',
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
     * @return Collection<Object>
     */
    public function getObjectsFromRepository($namespace = null)
    {
        // Set default values if some parameters are missing
        $this->cleanAndSetQueryParams(array(
            'criteria' => $this->criteria,
            'limit'    => $this->limit,
            'sort'     => $this->sort,
            'page'     => $this->page,
            'offset'   => $this->offset
        ));

        // Count objects according to a given criteria
        $this->totalCount = $this->countObjects($namespace);

        // Retrieve objects according to a given criteria
        $this->objects = $this
            ->findByQueryBuilder($namespace)
            ->getQuery()
            ->execute();

        return $this;
    }

    /**
     * Return a Query Builder to find objects according to params
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function findByQueryBuilder($namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;
        $qb = $this
            ->objectManager
            ->getRepository($namespace)
            ->createQueryBuilder('object')
        ;

        $this->addSortToQueryBuilder($qb);
        $this->addCriteriaToQueryBuilder($qb);

        $qb->setFirstResult($this->computeOffsetWithPage());
        $qb->setMaxResults($this->limit);

        return $qb;
    }

    /**
     * Add query sort to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function addSortToQueryBuilder(QueryBuilder $qb)
    {
        foreach($this->sort as $field => $order) {
            $qb->addOrderBy(sprintf('object.%s', $field), $order);
        }

        return $qb;
    }

    /**
     * Add query criteria to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function addCriteriaToQueryBuilder(QueryBuilder $qb)
    {
        if(!$this->criteria) {
            return $qb;
        }

        foreach($this->criteria as $column => $value) {
            if(is_array($value)) {
                foreach($value as $k => $v) {
                    $qb->join(sprintf('object.%s', $column), $column);
                    $qb->andWhere(sprintf('%s.%s = :%s', $column, $k, $column));
                    $qb->setParameter($column, $v);
                }
            } else {
                $qb->andWhere(sprintf('object.%s = :%s', $column, $column));
                $qb->setParameter($column, $value);
            }
        }

        return $qb;
    }

    /**
     * Set the criteria
     *
     * @param array $criteria
     * @return $this
     */
    public function setCriteria($criteria = null)
    {
        $this->criteria = $this
            ->criteriaBuilder
            ->cleanCriteriaValue($criteria)
        ;

        return $this;
    }
    
    /**
     * Set the limit
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
     * Set the sort
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
     * Set the page
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
     * Set the offset
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
    public function cleanAndSetQueryParams(array $params)
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
                'rel'  => 'self',
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
                        $this->cleanCriteriaForLinks()
                    ),
                    true
                )
            ),
            'next'      => array(
                'rel'  => 'nav',
                'href' => $this->generateNextPageLink()
            ),
            'previous'  => array(
                'rel'  => 'nav',
                'href' => $this->generatePreviousPageLink()
            ),
            'first'     => array(
                'rel'  => 'nav',
                'href' => $this->generatePageLink(1)
            ),
            'last'      => array(
                'rel'  => 'nav',
                'href' => $this->generatePageLink($this->computeTotalPage())
            ),
        );
    }

    /**
     * Clean criteria to simplify complex array criteria into simple array
     *
     * @return array
     */
    public function cleanCriteriaForLinks()
    {
        $cleanedCriteria = array();
        foreach($this->criteria as $column => $value) {
            if(is_array($value)) {
                foreach($value as $k => $v) {
                    $cleanedCriteria[$k] = $v;
                }
            } else {
                $cleanedCriteria[$column] = $value;
            }
        }

        return $cleanedCriteria;
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
        $getKeyMethod = sprintf("get%s", ucfirst($this
            ->getClassIdentifier($itemNamespace)
        ));

        if(!$this->itemRoutes) {
            return sprintf("%s/%s.%s",
                $this->router->generate($this->currentRouteName, array(), true),
                $object->$getKeyMethod(),
                $this->format
            );
        } else {
            return $this->router->generate(
                $this->itemRoutes[$this->getCleanedObjectName($itemNamespace)],
                array(
                    '_format' => $this->format,
                    $this->getClassIdentifier($itemNamespace) => $object->$getKeyMethod()
                ),
                true
            );
        }
    }

    /**
     * Generate previous page link to navigate in hypermedia collection
     *
     * @return string
     */
    public function generatePreviousPageLink() {
        if ($this->page - 1 < 1) {
            return '';
        }

        return $this->router->generate(
            $this->currentRouteName,
            array_merge(
                array(
                    '_format'   => $this->format,
                    'page'      => $this->page-1,
                    'sort'      => $this->sort,
                    'limit'     => $this->limit,
                    'offset'    => $this->offset
                ),
                $this->cleanCriteriaForLinks()
            ),
            true
        );
    }

    /**
     * Generate next page link to navigate in hypermedia collection
     *
     * @return string
     */
    public function generateNextPageLink()
    {
        if ($this->page + 1 > $this->computeTotalPage()) {
            return '';
        }

        return $this->router->generate(
            $this->currentRouteName,
            array_merge(
                array(
                    '_format'   => $this->format,
                    'page'      => $this->page+1,
                    'sort'      => $this->sort,
                    'limit'     => $this->limit,
                    'offset'    => $this->offset
                ),
                $this->cleanCriteriaForLinks()
            ),
            true
        );
    }

    /**
     * Generate page link to navigate in hypermedia collection
     *
     * @return string
     */
    public function generatePageLink($page)
    {
        return $this->router->generate(
            $this->currentRouteName,
            array_merge(
                array(
                    '_format'   => $this->format,
                    'page'      => $page,
                    'sort'      => $this->sort,
                    'limit'     => $this->limit,
                    'offset'    => $this->offset
                ),
                $this->cleanCriteriaForLinks()
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
     * Compute the total page in a collection
     *
     * @return integer
     */
    public function computeTotalPage()
    {
        return ceil($this->totalCount / $this->limit);
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
     * @return \Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     */
    public function prepareQueryBuilderCount($namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        $qb = $this
            ->objectManager
            ->getRepository($this->objectNamespace)
            ->createQueryBuilder('object')
            ->select('COUNT(object.id)');

        $this->addCriteriaToQueryBuilder($qb);

        return $qb;
    }

    /**
     * Prepare a query to count objects
     *
     * @return \Doctrine\ORM\Query | Doctrine\ODM\MongoDB\Query\Query
     */
    public function prepareQueryCount($namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        return $this->prepareQueryBuilderCount($namespace)->getQuery();
    }

    /**
     * Count objects
     *
     * @return integer
     */
    public function countObjects($namespace)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        try {
            return intval($this->prepareQueryCount($namespace)->getSingleScalarResult());
        } catch(\Exception $e) {
            return 0;
        }
    }
}
