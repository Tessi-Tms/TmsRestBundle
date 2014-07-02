<?php

namespace Tms\Bundle\RestBundle\Formatter;

//use Doctrine\ORM\QueryBuilder;

/**
 * DoctrineOrmHypermediaFormatter is the doctrine orm collection formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class DoctrineOrmCollectionHypermediaFormatter extends AbstractDoctrineCollectionHypermediaFormatter
{
    /**
     * {@inheritdoc }
     */
    protected function addSortToQueryBuilder($qb)
    {
        foreach($this->sort as $field => $order) {
            $qb->addOrderBy(sprintf('object.%s', $field), $order);
        }

        return $qb;
    }

    /**
     * {@inheritdoc }
     */
    protected function addPaginationToQueryBuilder($qb)
    {
        $qb->setFirstResult($this->computeOffsetWithPage());
        $qb->setMaxResults($this->limit);
    }

    /**
     * {@inheritdoc }
     */
    protected function addCriteriaToQueryBuilder($qb)
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
     * {@inheritdoc }
     */
    protected function prepareCountQueryBuilder($namespace = null)
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
     * {@inheritdoc }
     */
    protected function countObjects($namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        return intval($this->prepareQueryCount($namespace)->getSingleScalarResult());
    }
}
