<?php

namespace Tms\Bundle\RestBundle\Formatter;

/**
 * InfoHypermediaFormatter is the info formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class InfoHypermediaFormatter extends AbstractHypermediaFormatter
{
    /**
     * Format raw data to have hypermedia metadata in output
     *
     * @return array
     */
    protected function formatMetadata()
    {
        return array(
            'type' => $this->getType(),
            AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_NAME => $this->getSerializerContextGroup()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function formatData()
    {
        return null;
    }

    /**
     * Format raw data to have hypermedia links in output
     *
     * @return array
     */
    protected function formatLinks()
    {
        return null;
    }

    /**
     * Give object type
     *
     * @return string
     */
    protected function getType()
    {
        return 'info';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSerializerContextGroup()
    {
        return AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_ITEM;
    }
}
