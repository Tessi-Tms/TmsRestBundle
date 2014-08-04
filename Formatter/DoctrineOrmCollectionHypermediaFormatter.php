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
    protected function addSortToQueryBuilder()
    {
        foreach ($this->sort as $field => $order) {
            $this->queryBuilder->addOrderBy(sprintf('%s.%s', $this->getAliasName(), $field), $order);
        }
    }

    /**
     * {@inheritdoc }
     */
    protected function addPaginationToQueryBuilder()
    {
        $this->queryBuilder->setFirstResult($this->computeOffsetWithPage());
        $this->queryBuilder->setMaxResults($this->limit);
    }

    /**
     * {@inheritdoc }
     */
    protected function addCriteriaToQueryBuilder()
    {
        if (!$this->criteria) {
            return;
        }

        foreach ($this->criteria as $column => $value) {
            if (is_array($value)) {
                // If the column is an association
                if (in_array($column, array_keys($this->getClassMetadata()->associationMappings))) {
                    foreach ($value as $k => $v) {
                        $this->queryBuilder->join(sprintf('%s.%s', $this->getAliasName(), $column), $column);
                        $this->queryBuilder->andWhere(sprintf('%s.%s = :%s', $column, $k, $column));
                        $this->queryBuilder->setParameter($column, $v);
                    }
                } else {
                    $this->queryBuilder->andWhere(sprintf('%s.%s IN (:%s)', $this->getAliasName(), $column, $column));
                    $this->queryBuilder->setParameter($column, $value);
                }
            } else {
                $this->queryBuilder->andWhere(sprintf('%s.%s = :%s', $this->getAliasName(), $column, $column));
                $this->queryBuilder->setParameter($column, $value);
            }
        }
    }

    /**
     * {@inheritdoc }
     */
    protected function prepareCountQueryBuilder()
    {
        $queryBuilder = clone $this->queryBuilder;

        return $queryBuilder->select(sprintf('COUNT(%s.id)', $this->getAliasName()));
    }

    /**
     * {@inheritdoc }
     */
    protected function countObjects()
    {
        return intval($this->prepareQueryCount()->getSingleScalarResult());
    }
}
