<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

final class AdvancedSpy extends OperationProcessor
{
    protected const GAME_UNIT_SPY_ID = 407;

    public function getFormula(): float
    {
        $guards = $this->getGuards();
        $total_units = $this->amount + $guards + 1;

        return (3 * $this->amount / (2 * $total_units)) - (3 * $guards / (2 * $total_units)) - $this->operation->getDifficulty() + $this->getRandomChance();
    }

    public function processPreOperation(): void
    {
        // Do nothing
    }

    public function processSuccess(): void
    {
        $player = $this->region->getPlayer();

        $population = 0;
        $worldRegions = $player->getWorldRegions();
        $regionCount = count($worldRegions);
        foreach ($worldRegions as $worldRegion) {
            $population += $worldRegion->getPopulation();
        }
        $this->addToOperationLog($this->translator->trans('Searching for player information...', [], 'operations'));

        // XXX TODO: number_format($resource, 0, '.', ',')

        $this->addToOperationLog($this->translator->trans('Cash: %value%', ['%value%' => $player->getResources()->getCash()], 'operations'));
        $this->addToOperationLog($this->translator->trans('Food: %value%', ['%value%' => $player->getResources()->getFood()], 'operations'));
        $this->addToOperationLog($this->translator->trans('Wood: %value%', ['%value%' => $player->getResources()->getWood()], 'operations'));
        $this->addToOperationLog($this->translator->trans('Steel: %value%', ['%value%' => $player->getResources()->getSteel()], 'operations'));

        $this->addToOperationLog($this->translator->trans('Population: %value%', ['%value%' => $population], 'operations'));
        $this->addToOperationLog($this->translator->trans('Regions: %value%', ['%value%' => $regionCount], 'operations'));
        $this->addToOperationLog($this->translator->trans('Networth: %value%', ['%value%' => $player->getNetworth()], 'operations'));
    }

    public function processFailed(): void
    {
        $spiesLost = (int)($this->amount * 0.05);

        foreach ($this->playerRegion->getWorldRegionUnits() as $worldRegionUnit) {
            if ($worldRegionUnit->getGameUnit()->getId() === self::GAME_UNIT_SPY_ID) {
                $worldRegionUnit->setAmount(($worldRegionUnit->getAmount() - $spiesLost));
                $this->worldRegionUnitRepository->save($worldRegionUnit);
            }
        }

//        $reportText = $this->translator->trans('%player% tried to spy on region %regionX%, %regionY% but failed.', [
//            '%player%' => $this->playerRegion->getPlayer()->getName(),
//            '%regionX%' => $this->region->getX(),
//            '%regionY%' => $this->region->getY(),
//        ], 'operations');
        $this->reportCreator->createReport($this->region->getPlayer(), time(), 'failed-spy-on-region', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ],Report::TYPE_GENERAL);

        $this->addToOperationLog($this->translator->trans('We failed to spy and lost %spies% spies', ['%spies%' => $spiesLost], 'operations'));
    }

    public function processPostOperation(): void
    {
        $player = $this->region->getPlayer();
        $player->getNotifications()->setGeneral(true);
        $this->playerRepository->save($player);
    }
}
