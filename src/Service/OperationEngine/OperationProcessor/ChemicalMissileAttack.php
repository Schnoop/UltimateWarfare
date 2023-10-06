<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

use FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

final class ChemicalMissileAttack extends OperationProcessor
{
    public function getFormula(): float
    {
        $specialOps = $this->getSpecialOps();
        $guards = $this->getGuards();
        $total_units = $specialOps + $guards + 1;

        return (3 * $specialOps / (2 * $total_units)) - (3 * $guards / (2 * $total_units)) - $this->operation->getDifficulty() + $this->getRandomChance();
    }

    public function processPreOperation(): void
    {
        foreach ($this->playerRegion->getWorldRegionUnits() as $worldRegionUnit) {
            if ($worldRegionUnit->getGameUnit()->getId() === $this->operation->getGameUnit()->getId()) {
                $worldRegionUnit->setAmount($worldRegionUnit->getAmount() - $this->amount);
                $this->worldRegionUnitRepository->save($worldRegionUnit);
            }
        }
    }

    public function processSuccess(): void
    {
        if ($this->amount * 100 > $this->region->getPopulation()) {
            $this->region->setPopulation(0);

            $reportText = $this->translator->trans('%player% launched a chemical missile attack against region %regionX%, %regionY% and killed all population.', [
                '%player%' => $this->playerRegion->getPlayer()->getName(),
                '%regionX%' => $this->region->getX(),
                '%regionY%' => $this->region->getY(),
            ], 'operations');

            $this->reportCreator->createReport($this->region->getPlayer(), time(), $reportText, [
                '%player%' => $this->playerRegion->getPlayer()->getName(),
                '%regionX%' => $this->region->getX(),
                '%regionY%' => $this->region->getY(),
            ]);

            $this->addToOperationLog($this->translator->trans('You killed all population!', [], 'operations'));
        } else {
            $populationKilled = $this->amount * 100;
            $this->region->setPopulation($this->region->getPopulation() - $populationKilled);

            $reportText = $this->translator->trans('%player% launched a chemical missile attack against region %regionX%, %regionY% and killed %population% population.', [
                '%player%' => $this->playerRegion->getPlayer()->getName(),
                '%population%' => $populationKilled,
                '%regionX%' => $this->region->getX(),
                '%regionY%' => $this->region->getY(),
            ], 'operations');

            $this->reportCreator->createReport($this->region->getPlayer(), time(), $reportText, [
                '%player%' => $this->playerRegion->getPlayer()->getName(),
                '%population%' => $populationKilled,
                '%regionX%' => $this->region->getX(),
                '%regionY%' => $this->region->getY(),
            ]);

            $this->addToOperationLog($this->translator->trans('You killed %population% population!', ['%population%' => $populationKilled], 'operations'));
        }

        $this->worldRegionRepository->save($this->region);
    }

    public function processFailed(): void
    {
        $specialOpsLost = intval($this->getSpecialOps() * 0.05);

        foreach ($this->playerRegion->getWorldRegionUnits() as $worldRegionUnit) {
            if ($worldRegionUnit->getGameUnit()->getId() === self::GAME_UNIT_SPECIAL_OPS_ID) {
                $worldRegionUnit->setAmount(intval($worldRegionUnit->getAmount() - $specialOpsLost));
                $this->worldRegionUnitRepository->save($worldRegionUnit);
            }
        }

        $reportText = $this->translator->trans('%player% tried to launched a chemical missile attack against region %regionX%, %regionY% but failed.', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ], 'operations');

        $this->reportCreator->createReport($this->region->getPlayer(), time(), $reportText);

        $this->addToOperationLog($this->translator->trans('We failed to our chemical missile attack and lost %specialOpsLost% Special Ops', ['%specialOpsLost%' => $specialOpsLost], 'operations'));
    }

    public function processPostOperation(): void
    {
        $player = $this->region->getPlayer();
        $player->getNotifications()->setAttacked(true);
        $this->playerRepository->save($player);
    }
}
