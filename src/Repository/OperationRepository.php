<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Repository;

use FrankProjects\UltimateWarfare\Entity\Operation;
use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Exception\OperationNotFoundException;

interface OperationRepository
{
    /**
     * @throws OperationNotFoundException
     */
    public function find(int $id): Operation;

    /**
     * @return Operation[]
     */
    public function findAll(): array;

    /**
     * @return Operation[]
     */
    public function findEnabled(): array;

    /**
     * @return Operation[]
     */
    public function findAvailableForPlayer(Player $player): array;

    public function remove(Operation $operation): void;

    public function save(Operation $operation): void;
}
