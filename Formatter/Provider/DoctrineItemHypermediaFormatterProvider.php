<?php

namespace Tms\Bundle\RestBundle\Formatter\Provider;

/**
 * DoctrineItemHypermediaFormatterProvider is the provider for
 * doctrine item formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class DoctrineItemHypermediaFormatterProvider extends AbstractFormatterProvider
{
    /**
     * {@inheritdoc }
     */
    protected function getFormatterClassName()
    {
        return 'Tms\Bundle\RestBundle\Formatter\DoctrineItemHypermediaFormatter';
    }
}
