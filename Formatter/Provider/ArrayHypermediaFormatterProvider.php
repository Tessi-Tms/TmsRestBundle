<?php

namespace Tms\Bundle\RestBundle\Formatter\Provider;

class ArrayHypermediaFormatterProvider extends AbstractFormatterProvider
{
    /**
     * {@inheritdoc }
     */
    protected function getFormatterClassName()
    {
        return 'Tms\Bundle\RestBundle\Formatter\ArrayHypermediaFormatter';
    }
}
