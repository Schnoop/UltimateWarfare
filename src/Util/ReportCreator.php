<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Util;

use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Repository\ReportRepository;

final class ReportCreator
{
    private ReportRepository $reportRepository;

    public function __construct(
        ReportRepository $reportRepository
    ) {
        $this->reportRepository = $reportRepository;
    }

    /**
     * @param  array<string, int|string>  $values
     */
    public function createReport(Player $player, int $timestamp, string $translationIdentifier, array $values = array(), int $type = Report::TYPE_ATTACKED): void
    {
        $report = Report::createForPlayer($player, $timestamp, $type, $translationIdentifier, $values);
        $this->reportRepository->save($report);
    }
}
