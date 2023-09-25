<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Repository\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FrankProjects\UltimateWarfare\Entity\World;
use FrankProjects\UltimateWarfare\Exception\WorldNotFoundException;
use FrankProjects\UltimateWarfare\Repository\WorldRepository;

final class DoctrineWorldRepository implements WorldRepository
{
    private EntityManagerInterface $entityManager;

    /**
     * @var EntityRepository <World>
     */
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository(World::class);
    }

    /**
     * @throws WorldNotFoundException
     */
    public function find(int $id): World
    {
        $world = $this->repository->find($id);
        if ($world === null) {
            throw new WorldNotFoundException();
        }
        return $world;
    }

    /**
     * @return World[]
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    /**
     * @param bool $public
     * @return World[]
     */
    public function findByPublic(bool $public): array
    {
        return $this->repository->findBy(['public' => $public]);
    }

    /**
     * XXX TODO: Improve with custom repository queries
     *
     * @param World $world
     */
    public function remove(World $world): void
    {
        foreach ($world->getWorldSectors() as $worldSector) {
            foreach ($worldSector->getWorldRegions() as $worldRegion) {
                $this->entityManager->remove($worldRegion);
            }

            $this->entityManager->remove($worldSector);
        }

        $this->entityManager->remove($world);
        $this->entityManager->flush();
    }

    public function save(World $world): void
    {
        $this->entityManager->persist($world);
        $this->entityManager->flush();
    }

    public function refresh(World $world): void
    {
        $this->entityManager->refresh($world);
    }
}
