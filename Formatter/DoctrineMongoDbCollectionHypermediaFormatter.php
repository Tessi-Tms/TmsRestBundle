<?php

namespace Tms\Bundle\RestBundle\Formatter;

/**
 * DoctrineMongoDbCollectionHypermediaFormatter is the doctrine mongoDB collection formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class DoctrineMongoDbCollectionHypermediaFormatter extends AbstractDoctrineCollectionHypermediaFormatter
{
    /**
     * {@inheritdoc }
     */
    protected function addSortToQueryBuilder()
    {
        $this->queryBuilder->sort($this->sort);
    }

    /**
     * {@inheritdoc }
     */
    protected function addPaginationToQueryBuilder()
    {
        $this->queryBuilder->skip($this->computeOffsetWithPage());
        $this->queryBuilder->limit($this->limit);
    }

    /**
     * {@inheritdoc }
     */
    protected function addCriteriaToQueryBuilder()
    {
        if (!$this->criteria) {
            return;
        }

        $class = new \ReflectionClass($this->queryBuilder);

        if ($class->hasMethod('match')) {
            foreach ($this->criteria as $criterionName => $criterionValue) {
                $this->queryBuilder->match($criterionName, $criterionValue);
            }
        } else {
            foreach ($this->criteria as $criterionName => $criterionValue) {
                $this->queryBuilder->field($criterionName)->equals($criterionValue);
            }
        }
    }

    /**
     * {@inheritdoc }
     */
    protected function prepareCountQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * {@inheritdoc }
     */
    protected function countObjects()
    {
        return $this->prepareCountQueryBuilder()->getQuery()->execute()->count();
    }
}
