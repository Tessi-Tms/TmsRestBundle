<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\OrderBy;

class OrderByBuilder
{
    /**
     * Check if an associative array for sorting elements is valid according to the allowed fields.
     * Remove the fields that are not valid.
     *
     * @param array $orderBy
     * @param array $fields
     * @return array
     */
    public function checkAndModify(array $orderBy, array $fields)
    {
        foreach ($orderBy as $field => $sortingValue) {
            if (!in_array($field, $fields)) {
                unset($orderBy[$field]);
            }
            if ('desc' !== strtolower($sortingValue) && 'asc' !== strtolower($sortingValue)) {
                unset($orderBy[$field]);
            }
        }

        return $orderBy;
    }
}