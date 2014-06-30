<?php

namespace Tms\Bundle\RestBundle\Formatter\Provider;

/**
 * AbstractFormatterProvider is abstract helper class to build formatter provider.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class AbstractFormatterProvider implements FormatterProviderInterface
{
    /**
     * The ordonned arguments coming from the dependency injection.
     */
    protected $arguments;

    /**
     * {@inheritdoc }
     */
    public function __construct()
    {
        $this->arguments = func_get_args();
    }

    /**
     * {@inheritdoc }
     */
    public function create($arguments = array())
    {
        $class = new ReflectionClass($this->getFormatterClassName());

        return $class->newInstanceArgs(array_merge($this->arguments, $arguments));
    }

    /**
     * Get the name of the class to build.
     *
     * @return string The name of the class.
     */
    abstract protected function getFormatterClassName();
}
