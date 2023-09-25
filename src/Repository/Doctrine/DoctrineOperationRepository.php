<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Repository\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FrankProjects\UltimateWarfare\Entity\Operation;
use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Exception\OperationNotFoundException;
use FrankProjects\UltimateWarfare\Repository\OperationRepository;

final class DoctrineOperationRepository implements OperationRepository
{
    private EntityManagerInterface $entityManager;

    /**
     * @var EntityRepository <Operation>
     */
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository(Operation::class);
    }

    /**
     * @throws OperationNotFoundException
     */
    public function find(int $id): Operation
    {
        $operation = $this->repository->find($id);
        if ($operation === null) {
            throw new OperationNotFoundException();
        }
        return $operation;
    }

    /**
     * @return Operation[]
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    /**
     * @return Operation[]
     */
    public function findEnabled(): array
    {
        return $this->entityManager->createQuery(
            'SELECT o FROM Game:Operation o WHERE o.enabled = 1'
        )
            ->getResult();
    }

    public function findAvailableForPlayer(Player $player): array
    {
        return $this->entityManager->createQuery(
            'SELECT o
            FROM Game:Operation o 
            JOIN Game:Research r WITH o.research = r
            JOIN Game:ResearchPlayer rp WITH r = rp.research
            WHERE o.enabled = 1 AND rp.player = :player AND rp.active = 1'
        )
            ->setParameter('player', $player)
            ->getArrayResult();
    }

    public function remove(Operation $operation): void
    {
        $this->entityManager->remove($operation);
        $this->entityManager->flush();
    }

    public function save(Operation $operation): void
    {
        $this->entityManager->persist($operation);
        $this->entityManager->flush();
    }
}
