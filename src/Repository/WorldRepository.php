<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Repository;

use FrankProjects\UltimateWarfare\Entity\World;
use FrankProjects\UltimateWarfare\Exception\WorldNotFoundException;

interface WorldRepository
{
    /**
     * @throws WorldNotFoundException
     */
    public function find(int $id): World;

    /**
     * @return World[]
     */
    public function findAll(): array;

    /**
     * @param bool $public
     * @return World[]
     */
    public function findByPublic(bool $public): array;

    public function remove(World $world): void;

    public function save(World $world): void;

    public function refresh(World $world): void;
}
