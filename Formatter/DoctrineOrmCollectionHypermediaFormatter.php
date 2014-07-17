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
    protected function addSortToQueryBuilder($queryBuilder)
    {
        foreach($this->sort as $field => $order) {
            $queryBuilder->addOrderBy(sprintf('%s.%s', $this->getAliasName(), $field), $order);
        }

        return $queryBuilder;
    }

    /**
     * {@inheritdoc }
     */
    protected function addPaginationToQueryBuilder($queryBuilder)
    {
        $queryBuilder->setFirstResult($this->computeOffsetWithPage());
        $queryBuilder->setMaxResults($this->limit);
    }

    /**
     * {@inheritdoc }
     */
    protected function addCriteriaToQueryBuilder($queryBuilder)
    {
        if(!$this->criteria) {
            return $queryBuilder;
        }

        foreach($this->criteria as $column => $value) {
            if(is_array($value)) {
                foreach($value as $k => $v) {
                    $queryBuilder->join(sprintf('%s.%s', $this->getAliasName(), $column), $column);
                    $queryBuilder->andWhere(sprintf('%s.%s = :%s', $column, $k, $column));
                    $queryBuilder->setParameter($column, $v);
                }
            } else {
                $queryBuilder->andWhere(sprintf('%s.%s = :%s', $this->getAliasName(), $column, $column));
                $queryBuilder->setParameter($column, $value);
            }
        }

        return $queryBuilder;
    }

    /**
     * {@inheritdoc }
     */
    protected function prepareCountQueryBuilder($namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        $queryBuilder = isset($this->queryBuilder) ? $this->queryBuilder : $this
            ->objectManager
            ->getRepository($namespace)
            ->createQueryBuilder($this->getAliasName())
        ;

        $queryBuilder->select(sprintf('COUNT(%s.id)', $this->getAliasName()));

        return $queryBuilder;
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
