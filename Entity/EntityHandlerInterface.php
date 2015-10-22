<?php

namespace Tms\Bundle\RestBundle\Entity;

/**
 * EntityHandlerInterface is the interface that a class should
 * implement to be used as an entity handler.
 *
 * @author Thomas Prelot
 */
interface EntityHandlerInterface
{
    /**
     * Get the related entity class.
     *
     * @return string The entity class.
     */
    public function getEntityClass();

    /**
     * Get the entity repository.
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository The repository.
     */
    public function getRepository();

    /**
     * Create a new entity.
     *
     * @param array $fields The fields.
     *
     * @return integer The id of the created entity.
     */
    public function create(array $fields);

    /**
     * Update an entity.
     *
     * @param integer $id     The id of the entity.
     * @param array   $fields The updated fields.
     */
    public function update($id, array $fields);

    /**
     * Delete an entity.
     *
     * @param integer $id The id of the entity.
     */
    public function delete($id);
}
