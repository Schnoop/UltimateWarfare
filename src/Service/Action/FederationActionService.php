<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\Federation;
use FrankProjects\UltimateWarfare\Entity\FederationNews;
use FrankProjects\UltimateWarfare\Entity\GameResource;
use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Repository\FederationNewsRepository;
use FrankProjects\UltimateWarfare\Repository\FederationRepository;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use FrankProjects\UltimateWarfare\Repository\ReportRepository;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FederationActionService
{
    private FederationRepository $federationRepository;
    private FederationNewsRepository $federationNewsRepository;
    private PlayerRepository $playerRepository;
    private ReportRepository $reportRepository;
    private TranslatorInterface $translator;

    public function __construct(
        FederationRepository $federationRepository,
        FederationNewsRepository $federationNewsRepository,
        PlayerRepository $playerRepository,
        ReportRepository $reportRepository,
        TranslatorInterface $translator
    ) {
        $this->federationRepository = $federationRepository;
        $this->federationNewsRepository = $federationNewsRepository;
        $this->playerRepository = $playerRepository;
        $this->reportRepository = $reportRepository;
        $this->translator = $translator;
    }

    public function createFederation(Player $player, string $federationName): void
    {
        $this->ensureFederationEnabled($player);

        if ($player->getFederation() !== null) {
            throw new RunTimeException($this->translator->trans('You are already in a Federation!', [], 'federation'));
        }

        if ($this->federationRepository->findByNameAndWorld($federationName, $player->getWorld()) !== null) {
            throw new RunTimeException($this->translator->trans('Federation with this name already exist!', [], 'federation'));
        }

        $federation = Federation::createForPlayer($player, $federationName);
        $this->federationRepository->save($federation);

        $player->setFederation($federation);
        $player->setFederationHierarchy(Player::FEDERATION_HIERARCHY_GENERAL);

        $this->playerRepository->save($player);
    }

    /**
     * @param array<string, string> $resources
     */
    public function sendAid(Player $player, int $playerId, array $resources): void
    {
        $this->ensureFederationEnabled($player);

        if ($player->getId() === $playerId) {
            throw new RunTimeException($this->translator->trans('You can not send to yourself!', [], 'federation'));
        }

        $aidPlayer = $this->playerRepository->find($playerId);
        if ($aidPlayer === null || $aidPlayer->getFederation()->getId() !== $player->getFederation()->getId()) {
            throw new RunTimeException($this->translator->trans('Player is not in your Federation!', [], 'federation'));
        }

        $resourceString = '';
        foreach ($resources as $resourceName => $amount) {
            if (!GameResource::isValid($resourceName)) {
                continue;
            }

            $amount = intval($amount);
            if ($amount <= 0) {
                continue;
            }

            $resourceAmount = $player->getResources()->getValueByName($resourceName);
            if ($amount > $resourceAmount) {
                throw new RunTimeException($this->translator->trans('You do not have enough %ressource%!', ['%ressource%' => $resourceName], 'federation'));
            }

            $player->getResources()->setValueByName($resourceName, $resourceAmount - $amount);
            $aidPlayerResourceAmount = $aidPlayer->getResources()->getValueByName($resourceName);
            $aidPlayer->getResources()->setValueByName($resourceName, $aidPlayerResourceAmount + $amount);

            if ($resourceString !== '') {
                $resourceString .= ', ';
            }
            $resourceString .= $amount . ' ' . $resourceName;
        }

        if ($resourceString !== '') {
            $news = $this->translator->trans('%sender% has sent %ressource% to %player%', ['%sender' => $player->getName(), '%ressource%' => $resourceString, '%player%' => $aidPlayer->getName()], 'federation');
            $federationNews = FederationNews::createForFederation($player->getFederation(), $news);
            $this->federationNewsRepository->save($federationNews);

            $aidPlayerNotifications = $aidPlayer->getNotifications();
            $aidPlayerNotifications->setAid(true);
            $aidPlayer->setNotifications($aidPlayerNotifications);
            $this->playerRepository->save($aidPlayer);
            $this->playerRepository->save($player);

            // $reportString = $this->translator->trans('%sender% has sent %ressource% to you', ['%sender' => $player->getName(), '%ressource%' => $resourceString], 'federation');
            $report = Report::createForPlayer($aidPlayer, time(), Report::TYPE_AID, 'ressource-sent-to-you', ['%sender' => $player->getName(), '%ressource%' => $resourceString]);
            $this->reportRepository->save($report);

            // $reportString = $this->translator->trans('You have send %ressource% to %player%', ['%ressource%' => $resourceString, '%player%' => $aidPlayer->getName()], 'federation');
            $report = Report::createForPlayer($player, time(), Report::TYPE_AID, 'ressource-sent-to-player', ['%ressource%' => $resourceString, '%player%' => $aidPlayer->getName()]);
            $this->reportRepository->save($report);
        }
    }

    public function removeFederation(Player $player): void
    {
        $this->ensureFederationEnabled($player);

        if ($player->getFederationHierarchy() < Player::FEDERATION_HIERARCHY_GENERAL) {
            throw new RunTimeException($this->translator->trans('You do not have permission to remove the Federation!', [], 'federation'));
        }

        $this->federationRepository->remove($player->getFederation());
    }

    public function changeFederationName(Player $player, string $federationName): void
    {
        $this->ensureFederationEnabled($player);

        if ($player->getFederationHierarchy() < Player::FEDERATION_HIERARCHY_GENERAL) {
            throw new RunTimeException($this->translator->trans('You do not have permission to change the Federation name!', [], 'federation'));
        }

        if ($this->federationRepository->findByNameAndWorld($federationName, $player->getWorld()) !== null) {
            throw new RunTimeException($this->translator->trans('Federation name already exist!', [], 'federation'));
        }

        $federation = $player->getFederation();
        $federation->setName($federationName);
        $this->federationRepository->save($federation);
    }

    public function leaveFederation(Player $player): void
    {
        $this->ensureFederationEnabled($player);

        if ($player->getFederationHierarchy() < 1 || $player->getFederationHierarchy() == 10) {
            throw new RunTimeException($this->translator->trans('You are not allowed to leave the Federation with this rank!', [], 'federation'));
        }

        $federation = $player->getFederation();
        $news = $this->translator->trans('%player% has left the Federation.', ['%player%' => $player->getName()], 'federation');
        $federationNews = FederationNews::createForFederation($player->getFederation(), $news);
        $this->federationNewsRepository->save($federationNews);

        $player->setFederation(null);
        $player->setFederationHierarchy(0);
        $this->playerRepository->save($player);

        $federation->setNetworth($federation->getNetworth() - $player->getNetworth());
        $federation->setRegions($federation->getRegions() - count($player->getWorldRegions()));
        $this->federationRepository->save($federation);
    }

    public function kickPlayer(Player $player, int $playerId): void
    {
        $this->ensureFederationEnabled($player);

        if ($player->getFederationHierarchy() < Player::FEDERATION_HIERARCHY_GENERAL) {
            throw new RunTimeException($this->translator->trans('You do not have permission to kick a player!', [], 'federation'));
        }

        if ($player->getId() === $playerId) {
            throw new RunTimeException($this->translator->trans('You can not kick yourself!', [], 'federation'));
        }

        $kickPlayer = $this->playerRepository->find($playerId);
        if ($kickPlayer === null || $kickPlayer->getFederation()->getId() !== $player->getFederation()->getId()) {
            throw new RunTimeException($this->translator->trans('Player is not in your Federation!', [], 'federation'));
        }

        $kickPlayer->setFederation(null);
        $kickPlayer->setFederationHierarchy(0);
        $this->playerRepository->save($kickPlayer);

        $news = $this->translator->trans('%player% kicked %kickplayer% from the Federation.', ['%player%' => $player->getName(), '%kickplayer%' => $kickPlayer->getName()], 'federation');
        $federationNews = FederationNews::createForFederation($player->getFederation(), $news);
        $this->federationNewsRepository->save($federationNews);

        $federation = $player->getFederation();
        $federation->setNetworth($federation->getNetworth() - $kickPlayer->getNetworth());
        $federation->setRegions($federation->getRegions() - count($kickPlayer->getWorldRegions()));
        $this->federationRepository->save($federation);

        // $reportString = $this->translator->trans('You have been kicked from Federation %federation%', ['%federation%' => $federation->getName()], 'federation');
        $report = Report::createForPlayer($kickPlayer, time(), Report::TYPE_GENERAL, 'kicked-from-federation', ['%federation%' => $federation->getName()]);
        $this->reportRepository->save($report);
    }

    public function updateLeadershipMessage(Player $player, string $message): void
    {
        $this->ensureFederationEnabled($player);

        if ($player->getFederationHierarchy() < Player::FEDERATION_HIERARCHY_GENERAL) {
            throw new RunTimeException($this->translator->trans('You do not have permission to update the leadership message!', [], 'federation'));
        }

        $federation = $player->getFederation();
        $federation->setLeaderMessage($message);
        $this->federationRepository->save($federation);
    }

    public function changePlayerHierarchy(Player $player, int $playerId, int $role): void
    {
        $this->ensureFederationEnabled($player);

        if ($player->getFederationHierarchy() < Player::FEDERATION_HIERARCHY_GENERAL) {
            throw new RunTimeException($this->translator->trans('You do not have permission to change ranks!', [], 'federation'));
        }

        $changePlayer = $this->playerRepository->find($playerId);
        if ($changePlayer === null || $changePlayer->getFederation()->getId() !== $player->getFederation()->getId()) {
            throw new RunTimeException($this->translator->trans('Player is not in your Federation!', [], 'federation'));
        }

        if ($role < 1 || $role > 10) {
            throw new RunTimeException($this->translator->trans('Invalid role!', [], 'federation'));
        }

        $changePlayer->setFederationHierarchy($role);
        $this->playerRepository->save($changePlayer);
        /**
         * XXX TODO!
         *
         * if ($role == 10)
         * $sql = $db->query("UPDATE player SET fedlvl = $rank WHERE id = $fed_player;");
         * $sql2 = $db->query("UPDATE player SET fedlvl = 9 WHERE id = $player_id;");
         *
         * <table class="table text">
         * <tr><td class="tabletop"><b>Changing Federation Owner</b></td></tr>
         *
         * <form action="" method="post" />
         * <tr><td>
         * <b>Are you sure you wanna do this?<br />
         * By accepting this you will give the federation to another player, and will lower your rank to Staff General!<br /><br />
         *
         * <input type="hidden" name="rank" value="<?php echo"$rank"; ?>">
         * <input type="hidden" name="player" value="<?php echo"$fed_player"; ?>">
         * <br />
         * <input type="submit" name="submit" value="Accept">
         * </td></tr>
         * </table>
         */
    }

    private function ensureFederationEnabled(Player $player): void
    {
        $world = $player->getWorld();
        if (!$world->getFederation()) {
            throw new RunTimeException($this->translator->trans('Federations not enabled!', [], 'federation'));
        }
    }
}
