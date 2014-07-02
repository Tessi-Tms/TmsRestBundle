<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Factory;

use Tms\Bundle\RestBundle\Formatter\Provider\FormatterProviderInterface;

class FormatterFactory
{
    private $formatterProviders = array();

    /**
     * Add a formatter provider.
     *
     * @param string                     $id                The id of the formatter provider.
     * @param FormatterProviderInterface $formatterProvider The formatter provider.
     */
    public function addFormatterProvider($id, FormatterProviderInterface $formatterProvider)
    {
        $this->formatterProviders[$id] = $formatterProvider;
    }

    /**
     * Add a formatter provider.
     *
     * @param string $id The id of the formatter provider.
     *
     * @return FormatterProviderInterface The provider.
     */
    public function getFormatterProvider($id)
    {
        if (!isset($this->formatterProviders[$id])) {
            throw new \LogicException(sprintf(
                'The provider "%s" is not defined.',
                $id
            ));
        }

        return $this->formatterProviders[$id];
    }

    /**
     * Create and return a hypermedia formatter.
     *
     * @param string $providerId The id of the formatter provider.
     * @param mixed  params      The list of arguments to pass to the constructor of the formatter.
     *
     * @return object A formatter.
     */
    public function create($providerId)
    {
        $provider = $this->getFormatterProvider($providerId);

        $arguments = func_get_args();
        array_shift($arguments);

        return $provider->create($arguments);
    }
}
