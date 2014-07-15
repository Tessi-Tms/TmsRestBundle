<?php

namespace Tms\Bundle\RestBundle\Formatter;

/**
 * InquireHypermediaFormatter is the inquire formatter.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class InquireHypermediaFormatter extends AbstractHypermediaFormatter
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
     * Set an action.
     *
     * @param string $name           The identifier name.
     * @param string $method         The HTTP method.
     * @param string $url            The url.
     * @param array  $requiredParams The required parameters.
     * @param array  $optionalParams The optional parameters.
     *
     * @return AbstractHypermediaFormatter This.
     */
    public function setAction(
        $name,
        $method,
        $url,
        array $requiredParams = array(),
        array $optionalParams = array()
    )
    {
        $this->actions[$name] = array(
            'href' => $url,
            'method' => $method,
            'requiredParams' => $requiredParams,
            'optionalParams' => $optionalParams
        );

        return $this;
    }

    /**
     * Format actions into a given layout for hypermedia
     *
     * @return array
     */
    protected function formatActions()
    {
        $actions = array();

        foreach ($this->actions as $name => $action) {
            $actions[$name] = array(
                'rel' => $name,
                'href' => $action['href'],
                'method' => $action['method'],
                'requiredParams' => $action['requiredParams'],
                'optionalParams' => $action['optionalParams']
            );
        }

        return $actions;
    }

    /**
     * Give object type
     *
     * @return string
     */
    protected function getType()
    {
        return 'inquire';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSerializerContextGroup()
    {
        return AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_ITEM;
    }
}
