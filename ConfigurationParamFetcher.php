<?php

namespace Tms\Bundle\RestBundle;

use Doctrine\Common\Util\Inflector;

class ConfigurationParamFetcher
{
    protected $configuration = array();

    /**
     * Constructor
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Get route pagination configuration
     *
     * @param  string $routeName
     * @return array | null
     */
    public function getRoutePaginationConfiguration($routeName)
    {
        if (isset($this->configuration['routes'][$routeName])) {
            return $this->configuration['routes'][$routeName]['pagination'];
        }

        return null;
    }

    /**
     * Fetch
     *
     * @param string $routeName
     * @return array
     */
    public function fetch($routeName)
    {
        $routeConfiguration = $this->getRoutePaginationConfiguration($routeName);

        if (null !== $routeConfiguration) {
            return $routeConfiguration;
        }

        return $this->configuration['default']['pagination'];
    }

    /**
     * Fetch default value
     *
     * @param  string $routeName
     * @param  string $key
     * @param  mixed  $value
     * @return boolean "true" if the value has been changed "false" otherwise
     */
    public function fetchDefaultValue($routeName, $key, & $value)
    {
        $configuration = $this->fetch($routeName);
        $defaultMethod = sprintf('fetchDefault%s', Inflector::classify($key));

        $rc = new \ReflectionClass($this);
        if (!$rc->hasMethod($defaultMethod)) {
            $defaultMethod = 'fetchDefault';
        }

        return call_user_func_array(
            array($this, $defaultMethod),
            array($configuration[$key], & $value)
        );
    }

    /**
     * fetch default limit
     *
     * @param  mixed $config
     * @param  mixed $value
     * @return boolean "true" if the value has been changed "false" otherwise
     */
    public static function fetchDefaultLimit($config, & $value)
    {
        if (null === $value) {
            $value = $config['default'];

            return true;
        }

        if ($value > $config['maximum']) {
            $value = $config['maximum'];

            return true;
        }

        return false;
    }

    /**
     * fetch default sort
     *
     * @param  mixed $config
     * @param  mixed $value
     * @return boolean "true" if the value has been changed "false" otherwise
     */
    public static function fetchDefaultSort($config, & $value)
    {
        if (empty($value)) {
            $value = array($config['field'] => $config['order']);

            return true;
        }

        return false;
    }

    /**
     * fetch defaultt
     *
     * @param  mixed $config
     * @param  mixed $value
     * @return boolean "true" if the value has been changed "false" otherwise
     */
    public static function fetchDefault($config, & $value)
    {
        if (empty($value)) {
            $value = $config['default'];

            return true;
        }

        return false;
    }
}
