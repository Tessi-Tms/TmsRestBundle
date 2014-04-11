<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Criteria;

class CriteriaBuilder
{
    const ORDER_ASC = 'asc';
    const ORDER_DESC = 'desc';
    
    /**
     * @var array
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
     * @param string|null  $route
     */
    public function clean(array &$parameters, $route = null)
    {
        if (!count($parameters)) {
            return $parameters;
        }

        $this->guessPaginationByRoute($route);

        $parameters['limit']  = $this->defineLimitValue($parameters['limit']);
        $parameters['sort']   = $this->defineSortValue($parameters['sort']);
        $parameters['offset'] = $this->defineOffsetValue($parameters['offset']);
        $parameters['page']   = $this->definePageValue($parameters['page']);

        foreach ($parameters['criteria'] as $name => $value) {
            if (null === $value) {
                unset($parameters['criteria'][$name]);
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    try {
                        $parameters['criteria'][$name][$k] = unserialize($v);
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
            $this->configuration = $this->configuration['default'];
        } else if (isset($this->configuration['routes'][$route])) {
            $this->configuration = array_merge(
                $this->configuration['routes'][$route],
                $this->configuration['default']
            );
        } else {
            $this->configuration = $this->configuration['default'];
        }
    }

    /**
     * Define the limit value according to the original value and the defined configuration of the pagination
     *
     * @param mixed $originalValue
     * @return integer
     */
    private function defineLimitValue($originalValue)
    {
        $defaultLimit = $this->configuration['pagination']['limit'];
        if (is_null($originalValue)) {
            if ($defaultLimit['default'] > $defaultLimit['maximum']) {
                return $defaultLimit['maximum'];
            }

            return $defaultLimit['default'];
        }

        if (intval($originalValue) > $defaultLimit['maximum']) {
            return $defaultLimit['maximum'];
        }

        return $originalValue;
    }
    
    /**
     * Define the sort values (FIELD & ORDER) according to the original value and the defined configuration of the pagination
     *
     * @param mixed $originalValue
     * @return string
     */
    private function defineSortValue($originalValue)
    {
        $defaultSort = $this->configuration['pagination']['sort'];
        $allowed_orders = array(self::ORDER_ASC, self::ORDER_DESC);

        if (is_null($originalValue['order']) && is_null($originalValue['field'])) {
            return $defaultSort;
        }
        
        if(!isset($originalValue['order']) || !in_array(
            strtolower($originalValue['order']),
            $allowed_orders
        )) {
            
            $originalValue['order'] = $defaultSort['order'];
        }
        
        if(!isset($originalValue['field'])) {
            
            $originalValue['field'] = $defaultSort['field'];
        }

        return $originalValue;
    }
 
    /**
     * Define the page value according to the original value and the defined configuration of the pagination
     *
     * @param mixed $originalValue
     * @return integer
     */
    private function definePageValue($originalValue)
    {
        $defaultPage = $this->configuration['pagination']['page'];
        if(is_null($originalValue)) {
            return $defaultPage['default'];
        }

        return $originalValue;
    }
    
    /**
     * Define the offset value according to the original value and the defined configuration of the pagination
     *
     * @param mixed $originalValue
     * @return integer
     */
    private function defineOffsetValue($originalValue)
    {
        $defaultOffset = $this->configuration['pagination']['offset'];
        if (is_null($originalValue)) {
            return $defaultOffset['default'];
        }

        return $originalValue;
    }
}
