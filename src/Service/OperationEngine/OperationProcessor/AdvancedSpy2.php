<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Service\OperationEngine\OperationProcessor;

final class AdvancedSpy2 extends OperationProcessor
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
        $this->addToOperationLog($this->translator->trans('Searching for player reports...', [], 'operations'));

        foreach ($player->getReports() as $report) {
            if ($report->getTimestamp() < time() && $report->getTimestamp() > time() - 86400) {
                $this->addToOperationLog($this->translator->trans('Report - %time%', ['%time%' => $report->getTimestamp()], 'operations'));
                $this->addToOperationLog($this->translator->trans($report->getTranslationIdentifier(), $report->getTranslationValues()));
            }
        }
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

        $this->reportCreator->createReport($this->region->getPlayer(), time(), 'failed-spy-on-region', [
            '%player%' => $this->playerRegion->getPlayer()->getName(),
            '%regionX%' => $this->region->getX(),
            '%regionY%' => $this->region->getY(),
        ], Report::TYPE_GENERAL);

        $this->addToOperationLog($this->translator->trans('We failed to spy and lost %spies% spies', ['%spies%' => $spiesLost], 'operations'));
    }

    public function processPostOperation(): void
    {
        $player = $this->region->getPlayer();
        $player->getNotifications()->setGeneral(true);
        $this->playerRepository->save($player);
    }
}
