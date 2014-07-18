<?php

namespace Tms\Bundle\RestBundle\Formatter\Provider;

/**
 * InfoHypermediaFormatterProvider is the provider for
 * info formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class InfoHypermediaFormatterProvider extends AbstractFormatterProvider
{
    /**
     * {@inheritdoc }
     */
    protected function getFormatterClassName()
    {
        return 'Tms\Bundle\RestBundle\Formatter\InfoHypermediaFormatter';
    }
}
