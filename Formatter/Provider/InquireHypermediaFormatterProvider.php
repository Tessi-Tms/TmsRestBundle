<?php

namespace Tms\Bundle\RestBundle\Formatter\Provider;

/**
 * InquireHypermediaFormatterProvider is the provider for
 * inquire formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class InquireHypermediaFormatterProvider extends AbstractFormatterProvider
{
    /**
     * {@inheritdoc }
     */
    protected function getFormatterClassName()
    {
        return 'Tms\Bundle\RestBundle\Formatter\InquireHypermediaFormatter';
    }
}
