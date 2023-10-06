<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

use FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

final class DestroyCash extends OperationProcessor
{
    protected const RESEARCH_DESTROY_CASH_LEVEL_1 = 600;
    protected const RESEARCH_DESTROY_CASH_LEVEL_2 = 601;
    protected const RESEARCH_DESTROY_CASH_LEVEL_3 = 602;
    protected const RESEARCH_DESTROY_CASH_LEVEL_4 = 603;
    protected const RESEARCH_DESTROY_CASH_LEVEL_5 = 604;


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
        $maxPercentage = 0;
        if ($this->hasResearched(self::RESEARCH_DESTROY_CASH_LEVEL_1)) {
            $maxPercentage = 2;
        }

        if ($this->hasResearched(self::RESEARCH_DESTROY_CASH_LEVEL_2)) {
            $maxPercentage = 4;
        }

        if ($this->hasResearched(self::RESEARCH_DESTROY_CASH_LEVEL_3)) {
            $maxPercentage = 6;
        }

        if ($this->hasResearched(self::RESEARCH_DESTROY_CASH_LEVEL_4)) {
            $maxPercentage = 8;
        }

        if ($this->hasResearched(self::RESEARCH_DESTROY_CASH_LEVEL_5)) {
            $maxPercentage = 10;
        }

        $random = mt_rand(1, $maxPercentage);
        $percentageDestroyed = round($random / 100);
        $player = $this->region->getPlayer();
        $cashDestroyed = (int)($player->getResources()->getCash() * $percentageDestroyed);
        $player->getResources()->addCash(-$cashDestroyed);
        $this->playerRepository->save($player);

        $this->addToOperationLog($this->translator->trans('You destroyed %percentageDestroyed% of the cash, %cashDestroyed% in total!', ['%percentageDestroyed%' => $percentageDestroyed . '%', '%cashDestroyed%' => $cashDestroyed], 'operations'));

        $this->reportCreator->createReport($this->region->getPlayer(), time(), 'destroycash-full-success', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%cash%' => $cashDestroyed,
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ]);
    }

    public function processFailed(): void
    {
        $specialOpsLost = (int)($this->getSpecialOps() * 0.05);

        foreach ($this->playerRegion->getWorldRegionUnits() as $worldRegionUnit) {
            if ($worldRegionUnit->getGameUnit()->getId() === self::GAME_UNIT_SPECIAL_OPS_ID) {
                $worldRegionUnit->setAmount(intval($worldRegionUnit->getAmount() - $specialOpsLost));
                $this->worldRegionUnitRepository->save($worldRegionUnit);
            }
        }

        $this->reportCreator->createReport($this->region->getPlayer(), time(), 'destroycash-failed', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ]);

        $this->addToOperationLog($this->translator->trans('We failed to destroy cash and lost %specialOpsLost% Special Ops', ['%specialOpsLost%' => $specialOpsLost], 'operations'));
    }

    public function processPostOperation(): void
    {
        $player = $this->region->getPlayer();
        $player->getNotifications()->setAttacked(true);
        $this->playerRepository->save($player);
    }
}
