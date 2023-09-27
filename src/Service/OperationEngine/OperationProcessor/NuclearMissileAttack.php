<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

use FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

final class NuclearMissileAttack extends OperationProcessor
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
        foreach ($this->region->getWorldRegionUnits() as $worldRegionUnit) {
            $this->worldRegionUnitRepository->remove($worldRegionUnit);
        }

        foreach ($this->region->getConstructions() as $construction) {
            $this->constructionRepository->remove($construction);
        }

        $this->region->setState(1);
        $this->region->setPlayer(null);
        $this->worldRegionRepository->save($this->region);

        $reportText = $this->translator->trans('%player% launched a nuclear missile attack against region %regionX%, %regionY% and destroyed everything.', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ], 'operations');

        $this->reportCreator->createReport($this->region->getPlayer(), time(), $reportText);

        $this->addToOperationLog($this->translator->trans('The region is fully destroyed, a high amount of toxic radiation will make the region unliveable for an unknown amount of time!', [], 'operations'));
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

        $reportText = $this->translator->trans('%player% tried to launch a nuclear missile attack against region %regionX%, %regionY% but failed.', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ], 'operations');

        $this->reportCreator->createReport($this->region->getPlayer(), time(), $reportText);

        $this->addToOperationLog($this->translator->trans('We failed to our nuclear missile attack and lost %specialOpsLost% Special Ops', ['%specialOpsLost%' => $specialOpsLost], 'operations'));
    }

    public function processPostOperation(): void
    {
        $player = $this->region->getPlayer();
        $player->getNotifications()->setAttacked(true);
        $this->playerRepository->save($player);
    }
}
