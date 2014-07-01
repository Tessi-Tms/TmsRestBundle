<?php

namespace Tms\Bundle\RestBundle\Formatter;

//use Doctrine\ORM\QueryBuilder;

/**
 * DoctrineOrmHypermediaFormatter is the doctrine orm collection formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class DoctrineOrmHypermediaFormatter extends DoctrineCollectionHypermediaFormatter
{
    /**
     * Add query sort to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function addSortToQueryBuilder(/*QueryBuilder*/ $qb)
    {
        foreach($this->sort as $field => $order) {
            $qb->addOrderBy(sprintf('object.%s', $field), $order);
        }

        return $qb;
    }

    /**
     * Add query pagination to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function addPaginationToQueryBuilder(/*QueryBuilder*/ $qb)
    {
        $qb->setFirstResult($this->computeOffsetWithPage());
        $qb->setMaxResults($this->limit);
    }

    /**
     * Add query criteria to a Query Builder
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    public function addCriteriaToQueryBuilder(/*QueryBuilder*/ $qb)
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
}
