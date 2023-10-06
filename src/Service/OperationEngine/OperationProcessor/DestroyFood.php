<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

use FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

final class DestroyFood extends OperationProcessor
{
    protected const RESEARCH_DESTROY_FOOD_LEVEL_1 = 605;
    protected const RESEARCH_DESTROY_FOOD_LEVEL_2 = 606;
    protected const RESEARCH_DESTROY_FOOD_LEVEL_3 = 607;
    protected const RESEARCH_DESTROY_FOOD_LEVEL_4 = 608;
    protected const RESEARCH_DESTROY_FOOD_LEVEL_5 = 609;

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
        if ($this->hasResearched(self::RESEARCH_DESTROY_FOOD_LEVEL_1)) {
            $maxPercentage = 5;
        }

        if ($this->hasResearched(self::RESEARCH_DESTROY_FOOD_LEVEL_2)) {
            $maxPercentage = 10;
        }

        if ($this->hasResearched(self::RESEARCH_DESTROY_FOOD_LEVEL_3)) {
            $maxPercentage = 15;
        }

        if ($this->hasResearched(self::RESEARCH_DESTROY_FOOD_LEVEL_4)) {
            $maxPercentage = 20;
        }

        if ($this->hasResearched(self::RESEARCH_DESTROY_FOOD_LEVEL_5)) {
            $maxPercentage = 25;
        }

        $random = mt_rand(1, $maxPercentage);
        $percentageDestroyed = round($random / 100);
        $player = $this->region->getPlayer();
        $foodDestroyed = (int)($player->getResources()->getFood() * $percentageDestroyed);
        $player->getResources()->addFood(-$foodDestroyed);
        $this->playerRepository->save($player);

        $this->addToOperationLog($this->translator->trans('You destroyed %percentageDestroyed% of the food, %foodDestroyed% in total!', ['%percentageDestroyed%' => $percentageDestroyed . '%', '%foodDestroyed%' => $foodDestroyed], 'operations'));

        $this->reportCreator->createReport($this->region->getPlayer(), time(), 'destroyfood-full-success', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%food%' => $foodDestroyed,
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ]);
    }

    public function processFailed(): void
    {
        $specialOpsLost = (int)($this->getSpecialOps() * 0.05);

        foreach ($this->playerRegion->getWorldRegionUnits() as $worldRegionUnit) {
            if ($worldRegionUnit->getGameUnit()->getId() === self::GAME_UNIT_SPECIAL_OPS_ID) {
                $worldRegionUnit->setAmount(($worldRegionUnit->getAmount() - $specialOpsLost));
                $this->worldRegionUnitRepository->save($worldRegionUnit);
            }
        }

        $this->reportCreator->createReport($this->region->getPlayer(), time(), 'destroyfood-failed', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ]);

        $this->addToOperationLog($this->translator->trans('We failed to destroy food and lost %specialOpsLost% Special Ops', ['%specialOpsLost%' => $specialOpsLost], 'operations'));
    }

    public function processPostOperation(): void
    {
        $player = $this->region->getPlayer();
        $player->getNotifications()->setAttacked(true);
        $this->playerRepository->save($player);
    }
}
