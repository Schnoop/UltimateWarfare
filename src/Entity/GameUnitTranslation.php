<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Entity;

use Knp\DoctrineBehaviors\Contract\Entity\TranslationInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslationTrait;

class GameUnitTranslation implements TranslationInterface
{

    use TranslationTrait;

    private ?int $id;
    private string $name;
    private string $nameMulti;
    private string $rowName;
    private string $image;
    private string $description;

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setNameMulti(string $nameMulti): void
    {
        $this->nameMulti = $nameMulti;
    }

    public function getNameMulti(): string
    {
        return $this->nameMulti;
    }

    public function setRowName(string $rowName): void
    {
        $this->rowName = $rowName;
    }

    public function getRowName(): string
    {
        return $this->rowName;
    }

    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
