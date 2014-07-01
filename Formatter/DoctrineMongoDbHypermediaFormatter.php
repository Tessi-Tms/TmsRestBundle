<?php

namespace Tms\Bundle\RestBundle\Formatter;

//use Doctrine\ODM\MongoDB\Query\Builder as QueryBuilder;

/**
 * DoctrineMongoDbHypermediaFormatter is the doctrine mongoDB collection formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class DoctrineMongoDbHypermediaFormatter extends DoctrineCollectionHypermediaFormatter
{
    /**
     * Add query sort to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function addSortToQueryBuilder(/*QueryBuilder*/ $qb)
    {
        $qb->sort($this->sort);

        return $qb;
    }

    /**
     * Add query pagination to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function addPaginationToQueryBuilder(/*QueryBuilder*/ $qb)
    {
        $qb->skip($this->computeOffsetWithPage());
        $qb->limit($this->limit);
    }

    /**
     * Add query criteria to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function addCriteriaToQueryBuilder(/*QueryBuilder*/ $qb)
    {
        if (!$this->criteria) {
            return $qb;
        }

        $class = new \ReflectionClass($qb);

        if ($class->hasMethod('match')) {
            foreach ($this->criteria as $criterionName => $criterionValue) {
                $qb->match($criterionName, $criterionValue);
            }
        } else {
            foreach ($this->criteria as $criterionName => $criterionValue) {
                $qb->field($criterionName)->equals($criterionValue);
            }
        }

        return $qb;
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
            ->createQueryBuilder()
        ;

        $this->addCriteriaToQueryBuilder($qb);

        return $qb;
    }

    /**
     * Count objects
     *
     * @return integer
     */
    public function countObjects($namespace)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        //try {
            return intval($this->prepareQueryCount($namespace)->count());
        /*} catch(\Exception $e) {
            return 0;
        }*/
    }
}
