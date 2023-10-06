<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Entity;

use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;

class Operation implements TranslatableInterface
{
    use TranslatableTrait;

    private ?int $id;
    private int $cost;
    private bool $enabled = true;
    private float $difficulty = 0.5;
    private string $subclass;
    private int $maxDistance;
    private Research $research;
    private GameUnit $gameUnit;

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCost(): int
    {
        return $this->cost;
    }

    public function setCost(int $cost): void
    {
        $this->cost = $cost;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getDifficulty(): float
    {
        return $this->difficulty;
    }

    public function setDifficulty(float $difficulty): void
    {
        $this->difficulty = $difficulty;
    }

    public function getMaxDistance(): int
    {
        return $this->maxDistance;
    }

    public function setMaxDistance(int $maxDistance): void
    {
        $this->maxDistance = $maxDistance;
    }

    public function getResearch(): Research
    {
        return $this->research;
    }

    public function setResearch(Research $research): void
    {
        $this->research = $research;
    }

    public function getGameUnit(): GameUnit
    {
        return $this->gameUnit;
    }

    public function setGameUnit(GameUnit $gameUnit): void
    {
        $this->gameUnit = $gameUnit;
    }

    public function getSubclass(): string
    {
        return $this->subclass;
    }

    public function setSubclass(string $subclass): void
    {
        $this->subclass = $subclass;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function __call(string $method, array $arguments = array()): mixed
    {
        return $this->proxyCurrentLocaleTranslation($method, $arguments);
    }
}
