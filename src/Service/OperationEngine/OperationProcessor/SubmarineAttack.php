<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

use FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

final class SubmarineAttack extends OperationProcessor
{
    protected const GAME_UNIT_SUBMARINE_ID = 403;
    protected const GAME_UNIT_SHIP_ID = 303;
    protected const SHIPS_KILLED_PER_SUBMARINE = 1;

    public function getFormula(): float
    {
        $specialOps = $this->getSpecialOps();
        $guards = $this->getGuards();
        $total_units = $specialOps + $guards + 1;

        return (3 * $specialOps / (2 * $total_units)) - (3 * $guards / (2 * $total_units)) - $this->operation->getDifficulty() + $this->getRandomChance();
    }

    public function processPreOperation(): void
    {
        // Do nothing
    }

    public function processSuccess(): void
    {
        $ships = 0;
        foreach ($this->region->getWorldRegionUnits() as $worldRegionUnit) {
            if ($worldRegionUnit->getGameUnit()->getId() == self::GAME_UNIT_SHIP_ID) {
                $ships = $ships + $worldRegionUnit->getAmount();
            }
        }

        if (($this->amount * self::SHIPS_KILLED_PER_SUBMARINE) > $ships) {
            foreach ($this->region->getWorldRegionUnits() as $worldRegionUnit) {
                if ($worldRegionUnit->getGameUnit()->getId() == self::GAME_UNIT_SHIP_ID) {
                    $this->worldRegionUnitRepository->remove($worldRegionUnit);
                    $this->addToOperationLog($this->translator->trans('You sunk %shipsDestroyed% %name%!', ['%shipsDestroyed%' => $ships, '%name%' => $worldRegionUnit->getGameUnit()->getNameMulti()], 'operations'));
                }
            }

            $this->addToOperationLog($this->translator->trans('You sunk all ships!', [], 'operations'));
            $reportText = $this->translator->trans('Somebody launched a Submarine attack against region %regionX%, %regionY% and sunk all ships.', [
                '%regionX%' => $this->region->getX(),
                '%regionY%' => $this->region->getY(),
            ], 'operations');
            $this->reportCreator->createReport($this->region->getPlayer(), time(), $reportText);
        } else {
            $shipsDestroyed = $this->amount * self::SHIPS_KILLED_PER_SUBMARINE;
            foreach ($this->region->getWorldRegionUnits() as $worldRegionUnit) {
                if ($worldRegionUnit->getGameUnit()->getId() == self::GAME_UNIT_SHIP_ID) {
                    $worldRegionUnit->setAmount($worldRegionUnit->getAmount() - $shipsDestroyed);
                    $this->worldRegionUnitRepository->save($worldRegionUnit);
                    $this->addToOperationLog($this->translator->trans('You sunk %shipsDestroyed% %name%!', ['%shipsDestroyed%' => $shipsDestroyed, '%name%' => $worldRegionUnit->getGameUnit()->getNameMulti()], 'operations'));
                }
            }

            $reportText = $this->translator->trans('Somebody launched a Submarine attack against region %regionX%, %regionY% and sunk %sunk% ships.', [
                '%sunk%' => $shipsDestroyed,
                '%regionX%' => $this->region->getX(),
                '%regionY%' => $this->region->getY(),
            ], 'operations');
            $this->reportCreator->createReport($this->region->getPlayer(), time(), $reportText);
        }
    }

    public function processFailed(): void
    {
        $specialOpsLost = (int)($this->getSpecialOps() * 0.05);
        $submarinesLost = (int)($this->amount * 0.2);

        foreach ($this->playerRegion->getWorldRegionUnits() as $worldRegionUnit) {
            if ($worldRegionUnit->getGameUnit()->getId() === self::GAME_UNIT_SPECIAL_OPS_ID) {
                $worldRegionUnit->setAmount(($worldRegionUnit->getAmount() - $specialOpsLost));
                $this->worldRegionUnitRepository->save($worldRegionUnit);
            }

            if ($worldRegionUnit->getGameUnit()->getId() === self::GAME_UNIT_SUBMARINE_ID) {
                $worldRegionUnit->setAmount(($worldRegionUnit->getAmount() - $submarinesLost));
                $this->worldRegionUnitRepository->save($worldRegionUnit);
            }
        }

        $reportText = $this->translator->trans('%player% tried to launch a Submarine attack against region %regionX%, %regionY% but failed.', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ], 'operations');
        $this->reportCreator->createReport($this->region->getPlayer(), time(), $reportText);

        $this->addToOperationLog($this->translator->trans('We failed our Submarine attack and lost %specialOpsLost% Special Ops and %submarinesLost% Submarines', ['%specialOpsLost%' => $specialOpsLost, '%submarinesLost%' => $submarinesLost], 'operations'));
    }

    public function processPostOperation(): void
    {
        $player = $this->region->getPlayer();
        $player->getNotifications()->setAttacked(true);
        $this->playerRepository->save($player);
    }
}
