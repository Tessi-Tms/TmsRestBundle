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
    protected function addSortToQueryBuilder($queryBuilder)
    {
        $queryBuilder->sort($this->sort);

        return $queryBuilder;
    }

    /**
     * {@inheritdoc }
     */
    protected function addPaginationToQueryBuilder($queryBuilder)
    {
        $queryBuilder->skip($this->computeOffsetWithPage());
        $queryBuilder->limit($this->limit);
    }

    /**
     * {@inheritdoc }
     */
    protected function addCriteriaToQueryBuilder($queryBuilder)
    {
        if (!$this->criteria) {
            return $queryBuilder;
        }

        $class = new \ReflectionClass($queryBuilder);

        if ($class->hasMethod('match')) {
            foreach ($this->criteria as $criterionName => $criterionValue) {
                $queryBuilder->match($criterionName, $criterionValue);
            }
        } else {
            foreach ($this->criteria as $criterionName => $criterionValue) {
                $queryBuilder->field($criterionName)->equals($criterionValue);
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

        $queryBuilder = $this
            ->objectManager
            ->getRepository($this->objectNamespace)
            ->createQueryBuilder()
        ;
        $queryBuilder = $this->addCriteriaToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * {@inheritdoc }
     */
    protected function countObjects($namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        return $this->prepareCountQueryBuilder($namespace)->getQuery()->execute()->count();
    }
}
