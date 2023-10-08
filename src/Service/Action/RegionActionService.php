<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Entity\World;
use FrankProjects\UltimateWarfare\Entity\WorldRegion;
use FrankProjects\UltimateWarfare\Exception\WorldRegionNotFoundException;
use FrankProjects\UltimateWarfare\Repository\FederationRepository;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use FrankProjects\UltimateWarfare\Repository\WorldRegionRepository;
use FrankProjects\UltimateWarfare\Util\DistanceCalculator;
use FrankProjects\UltimateWarfare\Util\NetworthCalculator;
use FrankProjects\UltimateWarfare\Util\TimeCalculator;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegionActionService
{
    private WorldRegionRepository $worldRegionRepository;
    private PlayerRepository $playerRepository;
    private FederationRepository $federationRepository;
    private DistanceCalculator $distanceCalculator;
    private TimeCalculator $timeCalculator;
    private TranslatorInterface $translator;

    public function __construct(
        WorldRegionRepository $worldRegionRepository,
        PlayerRepository $playerRepository,
        FederationRepository $federationRepository,
        DistanceCalculator $distanceCalculator,
        TimeCalculator $timeCalculator,
        TranslatorInterface $translator
    ) {
        $this->worldRegionRepository = $worldRegionRepository;
        $this->playerRepository = $playerRepository;
        $this->federationRepository = $federationRepository;
        $this->distanceCalculator = $distanceCalculator;
        $this->timeCalculator = $timeCalculator;
        $this->translator = $translator;
    }

    /**
     * @return array<int<0, max>, array<string, WorldRegion|string>>
     */
    public function getAttackFromWorldRegionList(WorldRegion $worldRegion, Player $player): array
    {
        if ($worldRegion->getPlayer() === null) {
            throw new RuntimeException($this->translator->trans('Can not attack region without owner!', [], 'region'));
        }

        if ($worldRegion->getPlayer()->getId() == $player->getId()) {
            throw new RuntimeException($this->translator->trans('Can not attack your own region!', [], 'region'));
        }

        $playerRegions = [];
        foreach ($player->getWorldRegions() as $playerWorldRegion) {
            $travelTime = $this->distanceCalculator->calculateDistanceTravelTime(
                $playerWorldRegion->getX(),
                $playerWorldRegion->getY(),
                $worldRegion->getX(),
                $worldRegion->getY()
            );
            $travelTimeLeft = $this->timeCalculator->calculateTimeLeft($travelTime);
            $playerRegions[] = [
                'region' => $playerWorldRegion,
                'travelTime' => $travelTimeLeft
            ];
        }

        return $playerRegions;
    }

    /**
     * @return array<int<0, max>, array<string, WorldRegion|int>>
     */
    public function getOperationAttackFromWorldRegionList(WorldRegion $worldRegion, Player $player): array
    {
        if ($worldRegion->getPlayer() === null) {
            throw new RuntimeException($this->translator->trans('Can not attack region without owner!', [], 'region'));
        }

        if ($worldRegion->getPlayer()->getId() == $player->getId()) {
            throw new RuntimeException($this->translator->trans('Can not attack your own region!', [], 'region'));
        }

        $playerRegions = [];
        foreach ($player->getWorldRegions() as $playerWorldRegion) {
            $distance = $this->distanceCalculator->calculateDistance(
                $playerWorldRegion->getX(),
                $playerWorldRegion->getY(),
                $worldRegion->getX(),
                $worldRegion->getY()
            );
            $playerRegions[] = [
                'region' => $playerWorldRegion,
                'distance' => $distance
            ];
        }

        return $playerRegions;
    }

    /**
     * @param int $worldRegionId
     * @param Player $player
     * @throws WorldRegionNotFoundException
     */
    public function buyWorldRegion(int $worldRegionId, Player $player): void
    {
        $worldRegion = $this->getWorldRegionByIdAndWorld($worldRegionId, $player->getWorld());
        $resources = $player->getResources();

        if ($worldRegion->getPlayer() !== null) {
            throw new RuntimeException($this->translator->trans('Region is already owned by somebody!', [], 'region'));
        }

        if ($resources->getCash() < $player->getRegionPrice()) {
            throw new RuntimeException($this->translator->trans('You do not have enough money!', [], 'region'));
        }

        $resources->setCash($resources->getCash() - $player->getRegionPrice());

        $player->setResources($resources);
        $player->setNetworth($player->getNetworth() + NetworthCalculator::NETWORTH_CALCULATOR_REGION);

        $worldRegion->setPlayer($player);

        $federation = $player->getFederation();

        if ($federation != null) {
            $federation->setRegions($federation->getRegions() + 1);
            $federation->setNetworth($federation->getNetworth() + NetworthCalculator::NETWORTH_CALCULATOR_REGION);
            $this->federationRepository->save($federation);
        }

        $this->playerRepository->save($player);
        $this->worldRegionRepository->save($worldRegion);
    }

    /**
     * @param int $worldRegionId
     * @param World $world
     * @return WorldRegion
     * @throws WorldRegionNotFoundException
     */
    public function getWorldRegionByIdAndWorld(int $worldRegionId, World $world): WorldRegion
    {
        $worldRegion = $this->worldRegionRepository->find($worldRegionId);

        if ($worldRegion === null) {
            throw new WorldRegionNotFoundException();
        }

        if ($worldRegion->getWorld()->getId() != $world->getId()) {
            throw new RuntimeException($this->translator->trans('World region is not part for your game world!', [], 'region'));
        }

        return $worldRegion;
    }

    /**
     * @param int $worldRegionId
     * @param Player $player
     * @return WorldRegion
     * @throws WorldRegionNotFoundException
     */
    public function getWorldRegionByIdAndPlayer(int $worldRegionId, Player $player): WorldRegion
    {
        $worldRegion = $this->getWorldRegionByIdAndWorld($worldRegionId, $player->getWorld());

        if ($worldRegion->getPlayer() === null) {
            throw new RuntimeException($this->translator->trans('World region has no owner!', [], 'region'));
        }

        if ($worldRegion->getPlayer()->getId() != $player->getId()) {
            throw new RuntimeException($this->translator->trans('World region is not yours!', [], 'region'));
        }


        return $worldRegion;
    }
}
