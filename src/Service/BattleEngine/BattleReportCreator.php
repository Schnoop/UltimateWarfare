<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\BattleEngine;

use FrankProjects\UltimateWarfare\Entity\Fleet;
use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Repository\ReportRepository;

final class BattleReportCreator
{
    private ReportRepository $reportRepository;

    public function __construct(
        ReportRepository $reportRepository
    ) {
        $this->reportRepository = $reportRepository;
    }

    public function createBattleWonReports(Fleet $fleet, int $timestamp): void
    {
        $targetWorldRegion = $fleet->getTargetWorldRegion();

        //$reportString = "You took region {$targetWorldRegion->getRegionName()} from {$targetWorldRegion->getPlayer()->getName()}";
        $this->createReport($fleet->getPlayer(), $timestamp, 'battle-region-taken-from-you', ['%region%' => $targetWorldRegion->getRegionName(), '%player%' => $targetWorldRegion->getPlayer()->getName()]);

        //$reportString = "Your region {$targetWorldRegion->getRegionName()} have been attacked by {$fleet->getPlayer()->getName()}, their forces were to big and we have lost the fight!";
        $this->createReport($targetWorldRegion->getPlayer(), $timestamp, 'battle-region-you-lost', ['%region%' => $targetWorldRegion->getRegionName(), '%player%' => $fleet->getPlayer()->getName()]);

        if ($targetWorldRegion->getPlayer()->getFederation() !== null) {
            //$reportString = "{$targetWorldRegion->getPlayer()->getName()} lost region {$targetWorldRegion->getRegionName()} to {$fleet->getPlayer()->getName()}";
            $this->createReport($targetWorldRegion->getPlayer(), $timestamp, 'battle-someone-lost-region', ['%player%' => $targetWorldRegion->getPlayer()->getName(), '%region%' => $targetWorldRegion->getRegionName(), '%player2%' => $fleet->getPlayer()->getName()]);
        }

        if ($fleet->getPlayer()->getFederation() !== null) {
            //$reportString = "{$fleet->getPlayer()->getName()} took region {$targetWorldRegion->getRegionName()} from {$targetWorldRegion->getPlayer()->getName()}";
            $this->createReport($fleet->getPlayer(), $timestamp, 'battle-region-taken-from-someone', ['%player%' => $fleet->getPlayer()->getName(), '%region%' => $targetWorldRegion->getRegionName(), '%player2%' => $targetWorldRegion->getPlayer()->getName()]);
        }
    }

    public function createBattleLostReports(Fleet $fleet, int $timestamp): void
    {
        $targetWorldRegion = $fleet->getTargetWorldRegion();

        //$reportString = "You attacked region {$targetWorldRegion->getRegionName()} but the defending forces were too strong.";
        $this->createReport($fleet->getPlayer(), $timestamp, 'battle-your-region-attack-failed', ['%region%' => $targetWorldRegion->getRegionName()]);

        //$reportString = "Your region {$targetWorldRegion->getRegionName()} have been attacked by {$fleet->getPlayer()->getName()} and won the fight!";
        $this->createReport($targetWorldRegion->getPlayer(), $timestamp, 'battle-your-region-attacked-defended', ['%region%' => $targetWorldRegion->getRegionName(), '%player%' => $fleet->getPlayer()->getName()]);

        if ($targetWorldRegion->getPlayer()->getFederation() !== null) {
            //$reportString = "{$targetWorldRegion->getPlayer()->getName()} was attacked by {$fleet->getPlayer()->getName()} on region {$targetWorldRegion->getRegionName()} but the defending troops won the fight.";
            $this->createReport($targetWorldRegion->getPlayer(), $timestamp, 'battle-region-attacked-defended', ['%player%' => $targetWorldRegion->getPlayer()->getName(), '%player2%' => $fleet->getPlayer()->getName(), '%region%' => $targetWorldRegion->getRegionName()]);
        }

        if ($fleet->getPlayer()->getFederation() !== null) {
            //$reportString = "{$fleet->getPlayer()->getName()} attacked region {$targetWorldRegion->getRegionName()} but the defender was too strong.";
            $this->createReport($fleet->getPlayer(), $timestamp, 'battle-region-attacked-but-defender-was-strong', ['%player%' => $fleet->getPlayer()->getName(), '%region%' => $targetWorldRegion->getRegionName()]);
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function createReport(Player $player, int $timestamp, string $translationIdentifier, array $values = array()): void
    {
        $report = Report::createForPlayer($player, $timestamp, Report::TYPE_ATTACKED, $translationIdentifier, $values);
        $this->reportRepository->save($report);
    }
}
