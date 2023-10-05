<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\Federation;
use FrankProjects\UltimateWarfare\Entity\FederationApplication;
use FrankProjects\UltimateWarfare\Entity\FederationNews;
use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Repository\FederationApplicationRepository;
use FrankProjects\UltimateWarfare\Repository\FederationNewsRepository;
use FrankProjects\UltimateWarfare\Repository\FederationRepository;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use FrankProjects\UltimateWarfare\Repository\ReportRepository;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FederationApplicationActionService
{
    private FederationRepository $federationRepository;
    private FederationNewsRepository $federationNewsRepository;
    private FederationApplicationRepository $federationApplicationRepository;
    private PlayerRepository $playerRepository;
    private ReportRepository $reportRepository;
    private TranslatorInterface $translator;

    public function __construct(
        FederationRepository $federationRepository,
        FederationApplicationRepository $federationApplicationRepository,
        FederationNewsRepository $federationNewsRepository,
        PlayerRepository $playerRepository,
        ReportRepository $reportRepository,
        TranslatorInterface $translator
    ) {
        $this->federationRepository = $federationRepository;
        $this->federationApplicationRepository = $federationApplicationRepository;
        $this->federationNewsRepository = $federationNewsRepository;
        $this->playerRepository = $playerRepository;
        $this->reportRepository = $reportRepository;
        $this->translator = $translator;
    }

    public function acceptFederationApplication(Player $player, int $applicationId): void
    {
        $this->ensureFederationEnabled($player);

        $federationApplication = $this->getFederationApplication($player, $applicationId);
        if ($federationApplication->getPlayer()->getFederation() !== null) {
            throw new RunTimeException($this->translator->trans('Player is already in another Federation!', [], 'federation'));
        }

        if (count($player->getFederation()->getPlayers()) >= $player->getWorld()->getFederationLimit()) {
            throw new RunTimeException($this->translator->trans('Federation members world limit reached!', [], 'federation'));
        }

        // $news = $this->translator->trans('%player% has has been accepted into the Federation by %player2%', ['%player%' => $federationApplication->getPlayer()->getName(), '%player2%' => $player->getName()], 'federation');
        $federationNews = FederationNews::createForFederation($player->getFederation(), 'federation-accepted', ['%player%' => $federationApplication->getPlayer()->getName(), '%player2%' => $player->getName()]);
        $this->federationNewsRepository->save($federationNews);

        // $reportString = $this->translator->trans('You have been accepted in the Federation %federation%', ['%federation%' => $player->getFederation()->getName()], 'federation');
        $report = Report::createForPlayer(
            $federationApplication->getPlayer(),
            time(),
            Report::TYPE_GENERAL,
            'report-federation-accepted',
            ['%federation%' => $player->getFederation()->getName()]
        );
        $this->reportRepository->save($report);

        $applicationPlayer = $federationApplication->getPlayer();
        $applicationPlayer->setFederation($federationApplication->getFederation());
        $applicationPlayer->setFederationHierarchy(Player::FEDERATION_HIERARCHY_RECRUIT);
        $applicationPlayerNotifications = $applicationPlayer->getNotifications();
        $applicationPlayerNotifications->setGeneral(true);
        $applicationPlayer->setNotifications($applicationPlayerNotifications);
        $this->playerRepository->save($applicationPlayer);

        $federation = $federationApplication->getFederation();
        $federation->setNetworth($federation->getNetworth() + $federationApplication->getPlayer()->getNetworth());
        $federation->setRegions(
            $federation->getRegions() + count($federationApplication->getPlayer()->getWorldRegions())
        );
        $this->federationRepository->save($federation);

        $this->federationApplicationRepository->remove($federationApplication);
    }

    public function rejectFederationApplication(Player $player, int $applicationId): void
    {
        $this->ensureFederationEnabled($player);

        $federationApplication = $this->getFederationApplication($player, $applicationId);

        //$news = $this->translator->trans('%player% has has been rejected to join the Federation by %player2%', ['%player%' => $federationApplication->getPlayer()->getName(), '%player2%' => $player->getName()], 'federation');
        $federationNews = FederationNews::createForFederation($player->getFederation(), 'federation-rejected', ['%player%' => $federationApplication->getPlayer()->getName(), '%player2%' => $player->getName()]);
        $this->federationNewsRepository->save($federationNews);

        // $reportString = $this->translator->trans('You have been rejected by the Federation %federation%', ['%federation%' => $player->getFederation()->getName()], 'federation');
        $report = Report::createForPlayer(
            $federationApplication->getPlayer(),
            time(),
            Report::TYPE_GENERAL,
            'federation-rejected',
            ['%federation%' => $player->getFederation()->getName()]
        );
        $this->reportRepository->save($report);

        $this->federationApplicationRepository->remove($federationApplication);
    }

    public function sendFederationApplication(Player $player, Federation $federation, string $application): void
    {
        $this->ensureFederationEnabled($player);

        if ($player->getFederation() !== null) {
            throw new RunTimeException($this->translator->trans('You are already in a Federation!', [], 'federation'));
        }

        if ($federation->getWorld()->getId() !== $player->getWorld()->getId()) {
            throw new RunTimeException($this->translator->trans('Federation is not in your world!', [], 'federation'));
        }

        $federationApplication = FederationApplication::createForFederation($federation, $player, $application);
        $this->federationApplicationRepository->save($federationApplication);
    }

    private function ensureFederationEnabled(Player $player): void
    {
        $world = $player->getWorld();
        if (!$world->getFederation()) {
            throw new RunTimeException($this->translator->trans('Federations not enabled!', [], 'federation'));
        }
    }

    private function getFederationApplication(Player $player, int $federationApplicationId): FederationApplication
    {
        $federationApplication = $this->federationApplicationRepository->findByIdAndWorld(
            $federationApplicationId,
            $player->getWorld()
        );

        if ($federationApplication === null) {
            throw new RunTimeException($this->translator->trans('FederationApplication does not exist!', [], 'federation'));
        }

        if ($player->getFederation() === null) {
            throw new RunTimeException($this->translator->trans('You are not in a Federation!', [], 'federation'));
        }

        if ($player->getFederation()->getId() !== $federationApplication->getFederation()->getId()) {
            throw new RunTimeException($this->translator->trans('FederationApplication does not belong to your Federation!', [], 'federation'));
        }

        return $federationApplication;
    }
}
