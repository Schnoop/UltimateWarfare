<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\GameEngine\Processor;

use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Repository\ReportRepository;
use FrankProjects\UltimateWarfare\Repository\ResearchPlayerRepository;
use FrankProjects\UltimateWarfare\Service\GameEngine\Processor;
use FrankProjects\UltimateWarfare\Service\NetworthUpdaterService;

final class ResearchProcessor implements Processor
{
    private ResearchPlayerRepository $researchPlayerRepository;
    private ReportRepository $reportRepository;
    private NetworthUpdaterService $networthUpdaterService;

    public function __construct(
        ResearchPlayerRepository $researchPlayerRepository,
        ReportRepository $reportRepository,
        NetworthUpdaterService $networthUpdaterService,
    ) {
        $this->researchPlayerRepository = $researchPlayerRepository;
        $this->reportRepository = $reportRepository;
        $this->networthUpdaterService = $networthUpdaterService;
    }

    public function run(int $timestamp): void
    {
        $researches = $this->researchPlayerRepository->getNonActiveCompletedResearch($timestamp);

        foreach ($researches as $researchPlayer) {
            $researchPlayer->setActive(true);

            $player = $researchPlayer->getPlayer();

            $research = $researchPlayer->getResearch();
            $finishedTimestamp = $researchPlayer->getTimestamp() + $research->getTimestamp();
            //$message = $this->translator->trans('You successfully researched a new technology: %research%', ['%research%' => $research->translate()->getName()], 'research');
            $report = Report::createForPlayer($player, $finishedTimestamp, Report::TYPE_GENERAL, 'technology-researched', ['%research%' => $research->translate()->getName()]);

            $this->reportRepository->save($report);
            $this->researchPlayerRepository->save($researchPlayer);

            $this->networthUpdaterService->updateNetworthForPlayer($player);
        }
    }
}
