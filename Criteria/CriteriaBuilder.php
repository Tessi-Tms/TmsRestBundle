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
    private $pagination;

    /**
     * Constructor
     *
     * @param array $pagination
     */
    public function __construct(array $pagination)
    {
        $this->pagination = $pagination;
    }

    /**
     * Clean the criteria according to a list of given parameters and eventually a route name
     *
     * @param array        $parameters
     * @param string|null  $route
     * @return array
     */
    public function clean(array $parameters, $route = null)
    {
        if (!count($parameters)) {
            return $parameters;
        }

        foreach ($parameters as $name => $value) {
            if ('limit' === $name) {
                $parameters[$name] = $this->defineLimitParameter($value, $this->guessPaginationLimitByRoute($route));
                continue;
            }

            if (null === $value) {
                unset($parameters[$name]);
            }
        }

        return $parameters;
    }

    /**
     * Guess Pagination Limit by Route
     *
     * @param string $route
     * @return array
     */
    private function guessPaginationLimitByRoute($route)
    {
        if (isset($this->pagination[$route])) {
            return $this->pagination[$route];
        }

        return $this->pagination['default_configuration'];
    }

    /**
     * Define the limit parameter according to the original value and the defined configuration of the pagination
     *
     * @param integer $originalValue
     * @param array $pagination
     * @return integer
     */
    private function defineLimitParameter($originalValue, array $pagination)
    {
        if (null === $originalValue) {
            if ($pagination['default'] > $pagination['maximum']) {
                return $pagination['maximum'];
            }

            return $pagination['default'];
        }

        if ($originalValue > $pagination['maximum']) {
            return $pagination['maximum'];
        }

        return $originalValue;
    }
}