<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Entity;

use Knp\DoctrineBehaviors\Contract\Entity\TranslationInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslationTrait;
class GameUnitTypeTranslation implements TranslationInterface
{
    use TranslationTrait;

    private string $name;
    private string $imageDir;

    private ?int $id;

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

    public function setImageDir(string $imageDir): void
    {
        $this->imageDir = $imageDir;
    }

    public function getImageDir(): string
    {
        return $this->imageDir;
    }
}
