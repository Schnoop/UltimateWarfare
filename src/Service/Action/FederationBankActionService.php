<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\FederationNews;
use FrankProjects\UltimateWarfare\Entity\GameResource;
use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Repository\FederationNewsRepository;
use FrankProjects\UltimateWarfare\Repository\FederationRepository;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FederationBankActionService
{
    private FederationRepository $federationRepository;
    private FederationNewsRepository $federationNewsRepository;
    private PlayerRepository $playerRepository;
    private TranslatorInterface $translator;

    public function __construct(
        FederationRepository $federationRepository,
        FederationNewsRepository $federationNewsRepository,
        PlayerRepository $playerRepository,
        TranslatorInterface $translator
    ) {
        $this->federationRepository = $federationRepository;
        $this->federationNewsRepository = $federationNewsRepository;
        $this->playerRepository = $playerRepository;
        $this->translator = $translator;
    }

    /**
     * @param array<string, string> $resources
     */
    public function deposit(Player $player, array $resources): void
    {
        $this->ensureFederationEnabled($player);
        $federation = $player->getFederation();
        if ($federation === null) {
            throw new RuntimeException($this->translator->trans('You are not in a Federation!', [], 'federation'));
        }

        $resourceString = '';
        foreach ($resources as $resourceName => $amount) {
            if (!GameResource::isValid($resourceName)) {
                continue;
            }

            $amount = intval($amount);
            if ($amount <= 0) {
                continue;
            }
            $resourceAmount = $player->getResources()->getValueByName($resourceName);
            if ($amount > $resourceAmount) {
                throw new RuntimeException($this->translator->trans('You do not have enough %ressource%', ['%ressource%' => $resourceName], 'federation'));
            }

            $player->getResources()->setValueByName($resourceName, $resourceAmount - $amount);
            $federationResourceAmount = $federation->getResources()->getValueByName($resourceName);
            $federation->getResources()->setValueByName($resourceName, $federationResourceAmount + $amount);

            $resourceString = $this->addToResourceString($resourceString, $amount, $resourceName);
        }

        if ($resourceString !== '') {
            // $news = $this->translator->trans('%player% deposited %resources% to the Federation Bank', ['%player%' => $player->getName(), '%resources%' => $resourceString], 'federation');
            $federationNews = FederationNews::createForFederation($player->getFederation(), 'federation-deposited', ['%player%' => $player->getName(), '%resources%' => $resourceString]);
            $this->federationNewsRepository->save($federationNews);

            $this->playerRepository->save($player);
            $this->federationRepository->save($federation);
        }
    }

    /**
     * @param array<string, string> $resources
     */
    public function withdraw(Player $player, array $resources): void
    {
        $this->ensureFederationEnabled($player);

        $federation = $player->getFederation();
        if ($federation === null) {
            throw new RuntimeException($this->translator->trans('You are not in a Federation!', [], 'federation'));
        }

        if ($player->getFederationHierarchy() < Player::FEDERATION_HIERARCHY_CAPTAIN) {
            throw new RuntimeException($this->translator->trans('You do not have permission to use the Federation Bank!', [], 'federation'));
        }

        $resourceString = '';
        foreach ($resources as $resourceName => $amount) {
            if (!GameResource::isValid($resourceName)) {
                continue;
            }

            $amount = intval($amount);
            if ($amount <= 0) {
                continue;
            }

            $resourceAmount = $player->getResources()->getValueByName($resourceName);
            if ($amount > $resourceAmount) {
                throw new RuntimeException($this->translator->trans('Federation Bank does not have enough %ressource%!', ['%ressource%' => $resourceName], 'federation'));
            }

            $player->getResources()->setValueByName($resourceName, $resourceAmount + $amount);
            $federationResourceAmount = $federation->getResources()->getValueByName($resourceName);
            $federation->getResources()->setValueByName($resourceName, $federationResourceAmount - $amount);

            $resourceString = $this->addToResourceString($resourceString, $amount, $resourceName);
        }

        if ($resourceString !== '') {
            //$news = $this->translator->trans('%player% withdrew %resources% from the Federation Bank', ['%player%' => $player->getName(), '%resources%' => $resourceString], 'federation');
            $federationNews = FederationNews::createForFederation($player->getFederation(), 'federation-withdrew', ['%player%' => $player->getName(), '%resources%' => $resourceString]);
            $this->federationNewsRepository->save($federationNews);

            $this->playerRepository->save($player);
            $this->federationRepository->save($federation);
        }
    }

    private function ensureFederationEnabled(Player $player): void
    {
        $world = $player->getWorld();
        if (!$world->getFederation()) {
            throw new RuntimeException($this->translator->trans('Federations not enabled!', [], 'federation'));
        }
    }

    private function addToResourceString(string $resourceString, int $amount, string $resourceName): string
    {
        if ($resourceString !== '') {
            $resourceString .= ', ';
        }
        $resourceString .= $amount . ' ' . $resourceName;

        return $resourceString;
    }
}
