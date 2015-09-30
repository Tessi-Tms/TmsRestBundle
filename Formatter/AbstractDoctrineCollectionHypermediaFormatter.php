<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

use Symfony\Component\Routing\Router;
use Symfony\Component\Config\Loader\LoaderInterface;
use JMS\Serializer\Serializer;
use Tms\Bundle\RestBundle\Request\ParamReaderProviderInterface;
use Tms\Bundle\RestBundle\Request\RequestProviderInterface;

abstract class AbstractDoctrineCollectionHypermediaFormatter extends AbstractDoctrineHypermediaFormatter
{
    // Query params
    protected $criteria   = null;
    protected $limit      = null;
    protected $sort       = null;
    protected $page       = null;
    protected $offset     = null;
    protected $totalCount = null;
    protected $extraQuery = array();

    protected $linkedCriteria  = null;
    protected $forceTotalCount = false;
    protected $realPage        = null;

    // Cacou pour cacou
    protected $routeParameters = array();

    protected $queryBuilder = null;
    protected $aliasName    = null;
    protected $objects      = array();
    protected $itemRoutes   = null;


    /**
     * Constructor
     */
    public function __construct(
        Router $router,
        Serializer $serializer,
        LoaderInterface $routeLoader,
        ParamReaderProviderInterface $paramReaderProvider,
        RequestProviderInterface $requestProvider,
        $currentRouteName,
        $format,
        $routeParameters = array()
    )
    {
        $this->routeParameters = $routeParameters;

        parent::__construct(
            $router,
            $serializer,
            $routeLoader,
            $paramReaderProvider,
            $requestProvider,
            $currentRouteName,
            $format
        );
    }

    /**
     * Route parameters
     *
     * @return array
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * Get criteria
     *
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Get linked criteria
     *
     * @return array
     */
    public function getLinkedCriteria()
    {
        if (!empty($this->linkedCriteria)) {
            return $this->linkedCriteria;
        }

        return $this->getCriteria();
    }

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
                'page'                   => $this->getRealPage(),
                'pageCount'              => $this->computePageCount(),
                'totalCount'             => $this->totalCount,
                'limit'                  => $this->limit,
                'offset'                 => $this->offset
            ),
            self::cleanCriteria($this->getLinkedCriteria())
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
                    AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_NAME => AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_ITEM
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
        // Init query builder with criteria
        $this->initCriteriaQueryBuilder($namespace);

        // Count objects according to a given criteria
        if (!$this->forceTotalCount) {
            $this->totalCount = $this->countObjects();
        }

        // Add sort & pagination to query builder
        $this->addSortToQueryBuilder();
        $this->addPaginationToQueryBuilder();

        $this->objects = $this->queryBuilder->getQuery()->execute();

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
     * Return a Query Builder to find objects according to criteria
     *
     * @param string $namespace
     * @return Doctrine\ORM\QueryBuilder
     */
    protected function initCriteriaQueryBuilder($namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        $this->queryBuilder = isset($this->queryBuilder) ? $this->queryBuilder : $this
            ->objectManager
            ->getRepository($namespace)
            ->createQueryBuilder($this->getAliasName())
        ;

        $this->addCriteriaToQueryBuilder();
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
                'href' =>  $this->generateCurrentPageLink()
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
                $this->itemRoutes[$this->getCleanedObjectName($itemNamespace)]['route'],
                array_merge(
                    $this->itemRoutes[$this->getCleanedObjectName($itemNamespace)]['parameters'],
                    array(
                        '_format' => $this->format,
                        $this->getClassIdentifier($itemNamespace) => $object->$getKeyMethod()
                    )
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

        foreach ($actions as $actionName => $actionMethods) {
            foreach ($actionMethods as $action) {
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

                if (!isset($itemActions[$actionName])) {
                    $itemActions[$actionName] = array();
                }

                $itemActions[$actionName][] = $action;
            }
        }

        return $itemActions;
    }

    /**
     * Generate current page link to navigate in hypermedia collection
     *
     * @return string
     */
    protected function generateCurrentPageLink()
    {
        return $this->generatePageLink($this->getRealPage());
    }

    /**
     * Generate previous page link to navigate in hypermedia collection
     *
     * @return string
     */
    protected function generatePreviousPageLink()
    {
        $page = $this->getRealPage();

        if ($page - 1 < 1) {
            return '';
        }

        return $this->generatePageLink($page - 1);
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
        $page = $this->getRealPage();

        if ($page + 1 > $this->computeTotalPage()) {
            return '';
        }

        return $this->generatePageLink($page + 1);
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
                $this->getRouteParameters(),
                array(
                    '_format'   => $this->format,
                    'page'      => $page,
                    'sort'      => $this->sort,
                    'limit'     => $this->limit,
                    'offset'    => $this->offset,
                ),
                $this->extraQuery
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
        $offset = $this->computeOffsetWithPage(true);

        if ($offset > $this->totalCount) {
            return 0;
        } else {
            if ($this->totalCount - $offset > $this->limit) {
                return $this->limit;
            } else {
                return $this->totalCount-$offset;
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
        if ($this->limit === 0) {
            return 0;
        }

        return ceil($this->totalCount / $this->limit);
    }

    /**
     * Compute the offset according to the page number
     *
     * @return integer
     */
    protected function computeOffsetWithPage($useRealPage = false)
    {
        $page = $useRealPage ? $this->getRealPage() : $this->page;

        return $this->offset+($page-1)*$this->limit;
    }

    /**
     * {@inheritdoc }
     */
    protected function getSerializerContextGroup()
    {
        return AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_COLLECTION;
    }

    /**
     * Set a query builder by using specific method on repository
     *
     * @param string $methodName
     * @param string $aliasName
     * @param array  $arguments
     * @param string $namespace
     */
    public function initQueryBuilder($methodName, $aliasName, array $arguments = array(), $namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;
        $repository = $this
            ->objectManager
            ->getRepository($namespace)
        ;

        $this->queryBuilder = call_user_func_array(
            array($repository, $methodName),
            $arguments
        );
        $this->aliasName = $aliasName;

        return $this;
    }

    /**
     * Add a new route associated to an item namespace
     *
     * @param string $itemNamespace
     * @param string $itemRoute
     * @param array  $itemRouteParameters
     *
     * @return $this
     */
    public function addItemRoute($itemNamespace, $itemRoute, $itemRouteParameters = array())
    {
        $this->itemRoutes[$this->getCleanedObjectName($itemNamespace)] = array(
            'route'      => $itemRoute,
            'parameters' => $itemRouteParameters
        );

        return $this;
    }

    /**
     * Set the criteria
     *
     * @param array $criteria
     * @return $this
     */
    public function setCriteria(array $criteria = null, array $linkedCriteria = null)
    {
        $this->criteria = self::cleanCriteria($criteria);
        $this->linkedCriteria = self::cleanCriteria($linkedCriteria);

        return $this;
    }

    /**
     * Clean the criteria value
     *
     * @param array $criteria
     * @return array
     */
    public function cleanCriteria($criteria = null)
    {
        if(is_null($criteria)) {
            return array();
        }

        foreach ($criteria as $name => $value) {
            if (null === $value || $value === array()) {
                unset($criteria[$name]);

                continue;
            }

            if (is_array($value)) {
                $criteria[$name] = self::cleanCriteria($value);
            }
        }

        return $criteria;
    }

    /**
     * Force the total count
     *
     * @param integer $totalCount
     */
    public function forceTotalCount($realTotalCount, $realPage = null)
    {
        $this->totalCount = (int)$realTotalCount;
        $this->realPage = (int)$realPage;
        $this->forceTotalCount = true;
    }

    /**
     * Get the real page
     *
     * @param integer $totalCount
     */
    protected function getRealPage()
    {
        return $this->realPage ? $this->realPage : $this->page;
    }

    /**
     * Set the limit
     *
     * @param integer $limit
     * @return $this
     */
    public function setLimit($limit = null)
    {
        $this->limit = null !== $limit ? (int)$limit : null;

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
        $this->sort = $sort;

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
        $this->page = null !== $page ? (int)$page : null;

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
        $this->offset = null !== $offset ? (int)$offset : null;

        return $this;
    }

    /**
     * Set extra query
     *
     * @param array $extra
     * @return $this
     */
    public function setExtraQuery(array $extra)
    {
        $this->extraQuery = $extra;

        return $this;
    }
    /**
     * Add query sort to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     */
    abstract protected function addSortToQueryBuilder();

    /**
     * Add query pagination to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     */
    abstract protected function addPaginationToQueryBuilder();

    /**
     * Add query criteria to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder | Doctrine\ODM\MongoDB\Query\Builder
     */
    abstract protected function addCriteriaToQueryBuilder();

    /**
     * Count objects
     *
     * @return integer
     */
    abstract protected function countObjects();
}
