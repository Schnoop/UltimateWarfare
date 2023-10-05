<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\GameEngine\Processor;

use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Repository\ReportRepository;
use FrankProjects\UltimateWarfare\Repository\ResearchPlayerRepository;
use FrankProjects\UltimateWarfare\Service\GameEngine\Processor;
use FrankProjects\UltimateWarfare\Service\NetworthUpdaterService;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResearchProcessor implements Processor
{
    private ResearchPlayerRepository $researchPlayerRepository;
    private ReportRepository $reportRepository;
    private NetworthUpdaterService $networthUpdaterService;
    private TranslatorInterface $translator;

    public function __construct(
        ResearchPlayerRepository $researchPlayerRepository,
        ReportRepository $reportRepository,
        NetworthUpdaterService $networthUpdaterService,
        TranslatorInterface $translator
    ) {
        $this->researchPlayerRepository = $researchPlayerRepository;
        $this->reportRepository = $reportRepository;
        $this->networthUpdaterService = $networthUpdaterService;
        $this->translator = $translator;
    }

    public function run(int $timestamp): void
    {
        $researches = $this->researchPlayerRepository->getNonActiveCompletedResearch($timestamp);

        foreach ($researches as $researchPlayer) {
            $researchPlayer->setActive(true);

            $player = $researchPlayer->getPlayer();

            $research = $researchPlayer->getResearch();
            $finishedTimestamp = $researchPlayer->getTimestamp() + $research->getTimestamp();
            $message = $this->translator->trans('You successfully researched a new technology: %research%', ['%research%' => $research->translate()->getName()], 'research');
            $report = Report::createForPlayer($player, $finishedTimestamp, 2, $message);

            $this->reportRepository->save($report);
            $this->researchPlayerRepository->save($researchPlayer);

            $this->networthUpdaterService->updateNetworthForPlayer($player);
        }
    }
}
