<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Sort;

class SortBuilder
{
    /**
     * Prepare an associative array to sort elements according to the given allowed fields.
     *
     * @param string $sortParameter
     * @param array $allowedFields
     * @return array|null
     */
    public function prepare($sortParameter, array $allowedFields = array())
    {
        $orderBy = null;
        if (null === $sortParameter || !count($allowedFields)) {
            return $orderBy;
        }

        $fields = explode(',', $sortParameter);
        foreach ($fields as $field) {
            $cleanedField = self::cleanFieldName($field);
            if (in_array($cleanedField, $allowedFields)) {
                if (false === strpos($field, '-')) {
                    $orderBy[$cleanedField] = 'asc';
                } else {
                    $orderBy[$cleanedField] = 'desc';
                }
            }
        }

        return $orderBy;
    }

    /**
     * Clean the field name
     *
     * @param string $fieldName
     * @return string
     */
    private static function cleanFieldName($fieldName)
    {
        return str_replace('-', '', $fieldName);
    }
}