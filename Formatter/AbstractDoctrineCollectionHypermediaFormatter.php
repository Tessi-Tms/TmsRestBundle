<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

abstract class AbstractDoctrineCollectionHypermediaFormatter extends AbstractDoctrineHypermediaFormatter
{
    // Query params
    protected $criteria = null;
    protected $limit = null;
    protected $sort = null;
    protected $page = null;
    protected $offset = null;
    protected $totalCount = null;

    protected $queryBuilder = null;
    protected $aliasName = null;
    protected $objects;
    protected $itemRoutes = null;

    /**
     * Format metadata into a given layout for hypermedia
     *
     * @return array
     */
    protected function formatMetadata()
    {
        return array_merge(
            parent::formatMetadata(),
            array(
                'page'                   => $this->page,
                'pageCount'              => $this->computePageCount(),
                'totalCount'             => $this->totalCount,
                'limit'                  => $this->limit,
                'offset'                 => $this->offset
            ),
            $this->cleanCriteriaForLinks()
        );
    }

    /**
     * Format data into a given layout for hypermedia
     *
     * @return array
     */
    protected function formatData()
    {
        $data = array();
        $actions = $this->formatActions();

        foreach($this->objects as $object) {
            $data[] = array(
                'metadata' => array(
                    'type' => $this->getType(),
                    AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_NAME
                    => AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_ITEM
                ),
                'data'  => $object,
                'links' => array(
                    'self' => array(
                        'rel'  => 'self',
                        'href' => $this->generateItemLink($object)
                    )
                ),
                'actions' => $this->formatItemActions($actions, $object)
            );
        }

        return $data;
    }

    /**
     * Retrieve objects from repository
     *
     * @return Collection<Object>
     */
    protected function getObjectsFromRepository($namespace = null)
    {
        // Set default values if some parameters are missing
        $this->cleanAndSetQueryParams(array(
            'criteria' => $this->criteria,
            'limit'    => $this->limit,
            'sort'     => $this->sort,
            'page'     => $this->page,
            'offset'   => $this->offset
        ));


        // Retrieve objects according to a given criteria
        $this->objects = $this
            ->findByQueryBuilder($namespace)
            ->getQuery()
            ->execute();

        // Count objects according to a given criteria
        $this->totalCount = $this->countObjects($namespace);

        return $this;
    }

    /**
     * Return alias name to use in query builder
     *
     * @return string $aliasNae
     */
    protected function getAliasName()
    {
        return is_null($this->aliasName) ? 'object' : $this->aliasName;
    }

    /**
     * Return a Query Builder to find objects according to params
     *
     * @param string $namespace
     * @return Doctrine\ORM\QueryBuilder
     */
    protected function findByQueryBuilder($namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        $queryBuilder = isset($this->queryBuilder) ? $this->queryBuilder : $this
            ->objectManager
            ->getRepository($namespace)
            ->createQueryBuilder($this->getAliasName())
        ;
        

        $this->addSortToQueryBuilder($queryBuilder);
        $this->addPaginationToQueryBuilder($queryBuilder);
        $this->addCriteriaToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Define all params with configuration if some are not given
     *
     * @param array $parameters
     */
    protected function cleanAndSetQueryParams(array $params)
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
    protected function formatLinks()
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
            'nextPage'      => array(
                'rel'  => 'nav',
                'href' => $this->generateNextPageLink()
            ),
            'previousPage'  => array(
                'rel'  => 'nav',
                'href' => $this->generatePreviousPageLink()
            ),
            'firstPage'     => array(
                'rel'  => 'nav',
                'href' => $this->generatePageLink(1)
            ),
            'lastPage'      => array(
                'rel'  => 'nav',
                'href' => $this->generateLastPageLink()
            ),
        );
    }

    /**
     * Clean criteria to simplify complex array criteria into simple array
     *
     * @return array
     */
    protected function cleanCriteriaForLinks()
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
    protected function generateItemLink($object)
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
        
        return 0;
    }

    /**
     * Format item actions into a given layout for hypermedia
     *
     * @param array  $actions The patterns of the actions
     * @param object $object  The item object.
     *
     * @return array The actions of the item.
     */
    protected function formatItemActions(array $actions, $object)
    {
        $itemActions = array();

        foreach ($actions as $action) {
            $href = $action['href'];

            if (isset($this->objectPK)) {
                $classIdentifier = $this->objectPK;
            } else {
                $classIdentifier = $this->getClassIdentifier(get_class($object));
            }

            $id = sprintf('{%s}', $classIdentifier);

            if (strpos($href, $id) === false) {
                continue;
            }

            $getMethod = sprintf("get%s", ucfirst($classIdentifier));

            $action['href'] = str_replace(
                array($id, '{_format}'),
                array($object->$getMethod(), $this->format),
                $href
            );

            $itemActions[] = $action;
        }

        return $itemActions;
    }

    /**
     * Generate previous page link to navigate in hypermedia collection
     *
     * @return string
     */
    protected function generatePreviousPageLink()
    {
        if ($this->page - 1 < 1) {
            return '';
        }

        return $this->generatePageLink($this->page-1);
    }

    /**
     * Generate previous page link to navigate in hypermedia collection
     *
     * @return string
     */
    protected function generateLastPageLink()
    {
        if ($this->totalCount === 0) {
            return $this->generatePageLink(1);
        }

        return $this->generatePageLink($this->computeTotalPage());
    }

    /**
     * Generate next page link to navigate in hypermedia collection
     *
     * @return string
     */
    protected function generateNextPageLink()
    {
        if ($this->page + 1 > $this->computeTotalPage()) {
            return '';
        }

        return $this->generatePageLink($this->page+1);
    }

    /**
     * Generate page link to navigate in hypermedia collection
     *
     * @return string
     */
    protected function generatePageLink($page)
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
    protected function computePageCount()
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
    protected function computeTotalPage()
    {
        return ceil($this->totalCount / $this->limit);
    }

    /**
     * Compute the offset according to the page number
     *
     * @return integer
     */
    protected function computeOffsetWithPage()
    {
        return $this->offset+($this->page-1)*$this->limit;
    }

    /**
     * {@inheritdoc }
     */
    protected function getSerializerContextGroup()
    {
        return AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_COLLECTION;
    }

    /**
     *  Set a query builder by using specific method on repository
     *
     * @param string $methodName
     * @param string $aliasName
     * @param array  $arguments
     * @param string $namespace
     */
    public function initQueryBuilder($methodName, $aliasName, array $arguments = null, $namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;
        $repository = $this->queryBuilder = $this
            ->objectManager
            ->getRepository($namespace)
        ;

        $this->queryBuilder = isset($arguments) ? $repository->$methodName($arguments) : $repository->$methodName();
        $this->aliasName = $aliasName;

        return $this;
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
     * Prepare a query to count objects
     *
     * @return \Doctrine\ORM\Query | Doctrine\ODM\MongoDB\Query\Query
     */
    public function prepareQueryCount($namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        return $this->prepareCountQueryBuilder($namespace)->getQuery();
    }

    /**
     * Add query sort to a Query Builder
     *
     * @param Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     * @return Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     */
    abstract protected function addSortToQueryBuilder($queryBuilder);

    /**
     * Add query pagination to a Query Builder
     *
     * @param Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     * @return Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     */
    abstract protected function addPaginationToQueryBuilder($queryBuilder);

    /**
     * Add query criteria to a Query Builder
     *
     * @param Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     * @return Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     */
    abstract protected function addCriteriaToQueryBuilder($queryBuilder);

    /**
     * Prepare a query builder to count objects
     *
     * @return \Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     */
    abstract protected function prepareCountQueryBuilder($namespace = null);

    /**
     * Count objects
     *
     * @return integer
     */
    abstract protected function countObjects($namespace = null);
}
