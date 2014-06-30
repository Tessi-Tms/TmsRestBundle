<?php

namespace Tms\Bundle\RestBundle\Formatter\Provider;

/**
 * DoctrineMongoDbCollectionHypermediaFormatterProvider is the provider for
 * doctrine mongoDB collection formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class DoctrineMongoDbCollectionHypermediaFormatterProvider extends AbstractFormatterProvider
{
    /**
     * {@inheritdoc }
     */
    abstract protected function getFormatterClassName()
    {
        return 'Tms\Bundle\RestBundle\Formatter\DoctrineMongoDbHypermediaFormatter';
    }
}
