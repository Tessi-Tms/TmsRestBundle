<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Criteria;

class CriteriaBuilder
{
    /**
     * @var array
     *
     * Example:
     * array('route_name' => array(
     *     'default' => 20,
     *     'maximum' => 100,
     * ))
     */
    protected $configuration;

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
     * Clean the criteria according to a list of given parameters and eventually a route name
     *
     * @param array        $parameters
     * @param int|null     $limit
     * @param string|null  $route
     * @param string|null  $orderbydirection
     * @param string|null  $orderbycolumn
     */
    public function clean(array &$parameters, &$limit = null, $route = null, &$orderbydirection = 'ASC', &$orderbycolumn = 'id')
    {
        if (!count($parameters)) {
            return $parameters;
        }

        $limit = $this->defineLimitValue($limit, $this->guessPaginationByRoute($route));

        if(!in_array(strtolower($orderbydirection), array('asc', 'desc'))) {
            $orderbydirection = 'asc';
        }

        if(!isset($orderbycolumn)) {
            $orderbycolumn = 'id';
        }

        foreach ($parameters as $name => $value) {
            if (null === $value) {
                unset($parameters[$name]);
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    try {
                        $parameters[$name][$k] = unserialize($v);
                    } catch(\Exception $e) {
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Guess Pagination by Route
     *
     * @param string|null $route
     * @return array
     */
    private function guessPaginationByRoute($route = null)
    {
        if (null === $route || count($this->configuration['routes']) == 0) {
            return $this->configuration['default'];
        }

        if (isset($this->configuration['routes'][$route])) {
            return $this->configuration['routes'][$route];
        }

        return $this->configuration['default'];
    }

    /**
     * Define the limit value according to the original value and the defined configuration of the pagination
     *
     * @param mixed $originalValue
     * @param array $configuration
     * @return integer
     */
    private function defineLimitValue($originalValue, array $configuration)
    {
        $pagination = $configuration['pagination_limit'];
        if (null === $originalValue) {
            if ($pagination['default'] > $pagination['maximum']) {
                return $pagination['maximum'];
            }

            return $pagination['default'];
        }

        if (intval($originalValue) > $pagination['maximum']) {
            return $pagination['maximum'];
        }

        return $originalValue;
    }
}
