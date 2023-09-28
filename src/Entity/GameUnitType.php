<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;

class GameUnitType implements TranslatableInterface
{
    use TranslatableTrait;

    public const GAME_UNIT_TYPE_BUILDINGS = 1;
    public const GAME_UNIT_TYPE_DEFENCE_BUILDINGS = 2;
    public const GAME_UNIT_TYPE_SPECIAL_BUILDINGS = 3;
    public const GAME_UNIT_TYPE_UNITS = 4;
    public const GAME_UNIT_TYPE_SPECIAL_UNITS = 5;

    private ?int $id;

    /** @var Collection<int, GameUnit> */
    private Collection $gameUnits;

    public function __construct()
    {
        $this->gameUnits = new ArrayCollection();
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, GameUnit>
     */
    public function getGameUnits(): Collection
    {
        return $this->gameUnits;
    }

    /**
     * @param Collection<int, GameUnit> $gameUnits
     */
    public function setGameUnits(Collection $gameUnits): void
    {
        $this->gameUnits = $gameUnits;
    }
}
