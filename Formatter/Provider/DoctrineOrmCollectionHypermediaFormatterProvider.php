<?php

namespace Tms\Bundle\RestBundle\Formatter\Provider;

/**
 * DoctrineOrmCollectionHypermediaFormatterProvider is the provider for
 * doctrine orm collection formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class DoctrineOrmCollectionHypermediaFormatterProvider extends AbstractFormatterProvider
{
    /**
     * {@inheritdoc }
     */
    protected function getFormatterClassName()
    {
        return 'Tms\Bundle\RestBundle\Formatter\DoctrineOrmCollectionHypermediaFormatter';
    }
}
