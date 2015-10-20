<?php

namespace Tms\Bundle\RestBundle\Entity;

use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\Common\Persistence\ManagerRegistry;
use FOS\RestBundle\Util\Codes;
use Tms\Bundle\RestBundle\Exception\InvalidEntityFieldException;
use Tms\Bundle\RestBundle\Event\EntityEvent;
use Tms\Bundle\RestBundle\Event\EntityEvents;

/**
 * EntityHandler is a basic implementation of an entity handler.
 *
 * @author Thomas Prelot
 */
class EntityHandler implements EntityHandlerInterface
{
    /**
     * The database object manager.
     *
     * @var Doctrine\Common\Persistence\ObjectManager
     */
    protected $objectManager;

    /**
     * The repository of the entity.
     *
     * @var \Doctrine\Common\Persistence\ObjectRepository
     */
    protected $repository;

    /**
     * The validator to validate the entity.
     *
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * The class of the entity.
     *
     * @var string
     */
    protected $entityClass;

    /**
     * Constructor.
     *
     * @param ManagerRegistry          $managerRegistry The manager registry.
     * @param ValidatorInterface       $validator       The validator to validate the entity.
     * @param EventDispatcherInterface $eventDispatcher The event dispatcher.
     * @param string                   $entityClass     The class of the entity.
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ValidatorInterface $validator,
        EventDispatcherInterface $eventDispatcher,
        $entityClass
    )
    {
        $this->objectManager = $managerRegistry->getManager();
        $this->repository = $this->objectManager->getRepository($entityClass);
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $fields)
    {
        $entity = new $this->entityClass();

        foreach ($fields as $key => $value) {
            $setter = sprintf('set%s', ucfirst($key));

            $entity->$setter($value);
        }

        $errorList = $this->validator->validate($entity);

        if (count($errorList) === 0) {
            $this->eventDispatcher->dispatch(
                EntityEvents::PRE_CREATE,
                new EntityEvent($entity)
            );

            $this->objectManager->persist($entity);
            $this->objectManager->flush();

            $this->eventDispatcher->dispatch(
                EntityEvents::POST_CREATE,
                new EntityEvent($entity)
            );

            $this->objectManager->detach($entity);
        } else {
            throw new InvalidEntityFieldException($errorList);
        }

        return $entity->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $fields)
    {
        $entity = $this->repository->find($id);

        if (null === $entity) {
            throw new HttpException(
                Codes::HTTP_NOT_FOUND,
                sprintf(
                    'The entity "%s" of class "%s" was not found.',
                    $id,
                    get_class($entity)
                )
            );
        }

        foreach ($fields as $key => $value) {
            $setter = sprintf('set%s', ucfirst($key));

            $entity->$setter($value);
        }

        $errorList = $this->validator->validate($entity);

        if (count($errorList) === 0) {
            $this->eventDispatcher->dispatch(
                EntityEvents::PRE_UPDATE,
                new EntityEvent($entity)
            );

            $this->objectManager->flush();

            $this->eventDispatcher->dispatch(
                EntityEvents::POST_UPDATE,
                new EntityEvent($entity)
            );

            $this->objectManager->detach($entity);
        } else {
            throw new InvalidEntityFieldException($errorList);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $entity = $this->repository->find($id);

        if ($entity) {
            $this->eventDispatcher->dispatch(
                EntityEvents::PRE_DELETE,
                new EntityEvent($entity)
            );

            $this->objectManager->remove($entity);
            $this->objectManager->flush();

            $this->eventDispatcher->dispatch(
                EntityEvents::POST_DELETE,
                new EntityEvent($entity)
            );
        }
    }
}
