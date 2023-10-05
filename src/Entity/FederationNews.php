<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Entity;

class FederationNews
{
    private ?int $id;
    private int $timestamp;
    private string $translationIdentifier;
    private Federation $federation;

    /**
     * @var array <string, mixed>
     */
    private array $translationValues;

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getTranslationIdentifier(): string
    {
        return $this->translationIdentifier;
    }

    public function setTranslationIdentifier(string $translationIdentifier): void
    {
        $this->translationIdentifier = $translationIdentifier;
    }

    public function getFederation(): Federation
    {
        return $this->federation;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTranslationValues(): array
    {
        return $this->translationValues;
    }

    /**
     * @param  array<string, mixed>  $translationValues
     */
    public function setTranslationValues(array $translationValues): void
    {
        $this->translationValues = $translationValues;
    }

    public function setFederation(Federation $federation): void
    {
        $this->federation = $federation;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function createForFederation(Federation $federation, string $translationIdentifier, array $values = array()): FederationNews
    {
        $federationNews = new FederationNews();
        $federationNews->setFederation($federation);
        $federationNews->setTranslationIdentifier($translationIdentifier);
        $federationNews->setTimestamp(time());
        $federationNews->setTranslationValues($values);

        return $federationNews;
    }
}
