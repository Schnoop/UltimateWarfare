<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

use FrankProjects\UltimateWarfare\Entity\GameUnitType;
use FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

final class MissileAttack extends OperationProcessor
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
        $totalBuildings = 0;
        foreach ($this->region->getWorldRegionUnits() as $worldRegionUnit) {
            if ($worldRegionUnit->getGameUnit()->getGameUnitType()->getId() == GameUnitType::GAME_UNIT_TYPE_BUILDINGS) {
                $totalBuildings = $totalBuildings + $worldRegionUnit->getAmount();
            }
        }

        if (($this->amount / 2) > $totalBuildings) {
            $buildingsDestroyed = $totalBuildings;
            foreach ($this->region->getWorldRegionUnits() as $worldRegionUnit) {
                if ($worldRegionUnit->getGameUnit()->getGameUnitType()->getId() == GameUnitType::GAME_UNIT_TYPE_BUILDINGS) {
                    $this->worldRegionUnitRepository->remove($worldRegionUnit);
                    $this->addToOperationLog(
                        $this->translator->trans('You destroyed all %name% buildings!', ['%name%' => $worldRegionUnit->getGameUnit()->translate()->getName()], 'operations')
                    );
                }
            }

            $this->reportCreator->createReport($this->region->getPlayer(), time(), 'missile-full-success', [
                '%player%' => $this->playerRegion->getPlayer()->getName(),
                '%regionX%' => $this->region->getX(),
                '%regionY%' => $this->region->getY(),
            ]);
        } else {
            $buildingsDestroyed = (int)($this->amount / 2);
            foreach ($this->region->getWorldRegionUnits() as $worldRegionUnit) {
                if ($worldRegionUnit->getGameUnit()->getGameUnitType()->getId() == GameUnitType::GAME_UNIT_TYPE_BUILDINGS) {
                    $percentage = $worldRegionUnit->getAmount() / $totalBuildings;
                    $destroyed = (int)($buildingsDestroyed * $percentage);
                    $worldRegionUnit->setAmount($worldRegionUnit->getAmount() - $destroyed);
                    $this->worldRegionUnitRepository->save($worldRegionUnit);
                    $this->addToOperationLog(
                        $this->translator->trans('You destroyed %destroyed% %name% buildings!', ['%destroyed%' => $destroyed, '%name%' => $worldRegionUnit->getGameUnit()->translate()->getName()], 'operations')
                    );
                }
            }
            $this->reportCreator->createReport($this->region->getPlayer(), time(), 'missile-partly-success', [
                '%player%' => $this->playerRegion->getPlayer()->getName(),
                '%destroyed%' => $buildingsDestroyed,
                '%regionX%' => $this->region->getX(),
                '%regionY%' => $this->region->getY(),
            ]);
        }

        $this->addToOperationLog(
            $this->translator->trans('You destroyed %destroyed% buildings!', ['%destroyed%' => $buildingsDestroyed], 'operations')
        );
    }

    public function processFailed(): void
    {
        $troopsLost = intval($this->getSpecialOps() * 0.05);

        foreach ($this->playerRegion->getWorldRegionUnits() as $worldRegionUnit) {
            if ($worldRegionUnit->getGameUnit()->getId() === self::GAME_UNIT_SPECIAL_OPS_ID) {
                $worldRegionUnit->setAmount(intval($worldRegionUnit->getAmount() - $troopsLost));
                $this->worldRegionUnitRepository->save($worldRegionUnit);
            }
        }

        $this->reportCreator->createReport($this->region->getPlayer(), time(), 'missile-failed', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ]);

        $this->addToOperationLog($this->translator->trans('We failed our Missile Attack and lost %specialOpsLost% Special Ops', ['%specialOpsLost%' => $troopsLost], 'operations'));
    }

    public function processPostOperation(): void
    {
        $player = $this->region->getPlayer();
        $player->getNotifications()->setAttacked(true);
        $this->playerRepository->save($player);
    }
}
