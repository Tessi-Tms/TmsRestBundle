<?php

namespace Tms\Bundle\RestBundle\Formatter;

//use Doctrine\ODM\MongoDB\Query\Builder as QueryBuilder;

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
    protected function addSortToQueryBuilder($qb)
    {
        $qb->sort($this->sort);

        return $qb;
    }

    /**
     * {@inheritdoc }
     */
    protected function addPaginationToQueryBuilder($qb)
    {
        $qb->skip($this->computeOffsetWithPage());
        $qb->limit($this->limit);
    }

    /**
     * {@inheritdoc }
     */
    protected function addCriteriaToQueryBuilder($qb)
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
     * {@inheritdoc }
     */
    protected function prepareCountQueryBuilder($namespace = null)
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
     * {@inheritdoc }
     */
    protected function countObjects($namespace = null)
    {
        $namespace = is_null($namespace) ? $this->objectNamespace : $namespace;

        // TO DEFINE
        return 0;
    }
}
