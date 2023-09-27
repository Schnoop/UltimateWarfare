<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\GameResource;
use FrankProjects\UltimateWarfare\Entity\MarketItem;
use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Entity\Player\Resources;
use FrankProjects\UltimateWarfare\Entity\Report;
use FrankProjects\UltimateWarfare\Repository\MarketItemRepository;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use FrankProjects\UltimateWarfare\Repository\ReportRepository;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MarketActionService
{
    private MarketItemRepository $marketItemRepository;
    private PlayerRepository $playerRepository;
    private ReportRepository $reportRepository;
    private TranslatorInterface $translator;

    public function __construct(
        MarketItemRepository $marketItemRepository,
        PlayerRepository $playerRepository,
        ReportRepository $reportRepository,
        TranslatorInterface $translator
    ) {
        $this->marketItemRepository = $marketItemRepository;
        $this->playerRepository = $playerRepository;
        $this->reportRepository = $reportRepository;
        $this->translator = $translator;
    }

    public function buyOrder(Player $player, int $marketItemId): void
    {
        $this->ensureMarketEnabled($player);

        $marketItem = $this->getMarketItem($player, $marketItemId);

        if ($marketItem->getType() != MarketItem::TYPE_SELL) {
            throw new RunTimeException($this->translator->trans('Market order is not a buy order!', [], 'market'));
        }

        $this->ensureMarketItemNotOwnedByPlayer($marketItem, $player);

        $resources = $player->getResources();
        if ($marketItem->getPrice() > $resources->getCash()) {
            throw new RunTimeException($this->translator->trans('You do not have enough cash!', [], 'market'));
        }

        $resources->setCash($resources->getCash() - $marketItem->getPrice());
        $marketItemPlayer = $marketItem->getPlayer();
        $marketItemPlayerResources = $marketItemPlayer->getResources();
        $marketItemPlayerResources->setCash($marketItemPlayerResources->getCash() + $marketItem->getPrice());

        $resources = $this->addGameResources($marketItem, $resources);

        $marketItemPlayerNotifications = $marketItemPlayer->getNotifications();
        $marketItemPlayerNotifications->setMarket(true);

        $player->setResources($resources);
        $marketItemPlayer->setResources($marketItemPlayerResources);
        $marketItemPlayer->setNotifications($marketItemPlayerNotifications);

        $this->playerRepository->save($player);
        $this->playerRepository->save($marketItemPlayer);
        $this->marketItemRepository->remove($marketItem);

        $this->createBuyReports($marketItem, $player);
    }

    private function createBuyReports(MarketItem $marketItem, Player $player): void
    {
        $buyMessage = $this->translator->trans("You bought %amount% %resource%.", [
            '%amount%' => $marketItem->getAmount(),
            '%resource%' => $marketItem->getGameResource()
        ], "market");
        $this->createReport($player, $buyMessage);

        $sellMessage = $this->translator->trans("You sold %amount% %resource%.", [
            '%amount%' => $marketItem->getAmount(),
            '%resource%' => $marketItem->getGameResource()
        ], "market");
        $this->createReport($marketItem->getPlayer(), $sellMessage);
    }

    public function cancelOrder(Player $player, int $marketItemId): void
    {
        $this->ensureMarketEnabled($player);

        $marketItem = $this->getMarketItem($player, $marketItemId);

        if ($marketItem->getPlayer()->getId() != $player->getId()) {
            throw new RunTimeException($this->translator->trans('You can not cancel orders that do not belong to you!', [], 'market'));
        }

        $resources = $player->getResources();
        if ($marketItem->getType() == MarketItem::TYPE_BUY) {
            $resources->setCash($resources->getCash() + $marketItem->getPrice());
        } else {
            $resources = $this->addGameResources($marketItem, $resources);
        }

        $player->setResources($resources);
        $this->playerRepository->save($player);
        $this->marketItemRepository->remove($marketItem);
    }

    public function sellOrder(Player $player, int $marketItemId): void
    {
        $this->ensureMarketEnabled($player);

        $marketItem = $this->getMarketItem($player, $marketItemId);

        if ($marketItem->getType() != MarketItem::TYPE_BUY) {
            throw new RunTimeException($this->translator->trans('Market order is not a sell order!', [], 'market'));
        }

        $this->ensureMarketItemNotOwnedByPlayer($marketItem, $player);

        $resources = $player->getResources();
        $resources->setCash($resources->getCash() + $marketItem->getPrice());
        $marketItemPlayer = $marketItem->getPlayer();
        $marketItemPlayerResources = $marketItemPlayer->getResources();
        $marketItemPlayerResources->setCash($marketItemPlayerResources->getCash() - $marketItem->getPrice());

        $resources = $this->substractGameResources($marketItem, $resources);

        $marketItemPlayerNotifications = $marketItemPlayer->getNotifications();
        $marketItemPlayerNotifications->setMarket(true);

        $player->setResources($resources);
        $marketItemPlayer->setResources($marketItemPlayerResources);
        $marketItemPlayer->setNotifications($marketItemPlayerNotifications);
        $this->playerRepository->save($player);
        $this->playerRepository->save($marketItemPlayer);
        $this->marketItemRepository->remove($marketItem);

        $this->createSellReports($marketItem, $player);
    }

    private function createSellReports(MarketItem $marketItem, Player $player): void
    {
        $sellMessage = $this->translator->trans("You sold %amount% %resource%.", [
            '%amount%' => $marketItem->getAmount(),
            '%resource%' => $marketItem->getGameResource()
        ], "market");
        $this->createReport($player, $sellMessage);

        $buyMessage = $this->translator->trans("You bought %amount% %resource%.", [
            '%amount%' => $marketItem->getAmount(),
            '%resource%' => $marketItem->getGameResource()
        ], "market");
        $this->createReport($marketItem->getPlayer(), $buyMessage);
    }

    public function placeOffer(Player $player, string $gameResource, int $price, int $amount, string $action): void
    {
        $this->ensureValidGameResource($gameResource);

        if ($price < 1 || $amount < 1) {
            throw new RunTimeException($this->translator->trans('Invalid input!', [], 'market'));
        }

        $resources = $player->getResources();

        if ($action == MarketItem::TYPE_BUY) {
            if ($price > $resources->getCash()) {
                throw new RunTimeException($this->translator->trans('You do not have enough cash!', [], 'market'));
            }

            $resources->setCash($resources->getCash() - $price);
        } elseif ($action == MarketItem::TYPE_SELL) {
            $resources = $this->substractAndValidateGameResources($gameResource, $resources, $amount);
        } else {
            throw new RunTimeException($this->translator->trans('Invalid option!', [], 'market'));
        }

        $player->setResources($resources);
        $marketItem = MarketItem::createForPlayer($player, $gameResource, $amount, $price, $action);
        $this->marketItemRepository->save($marketItem);
        $this->playerRepository->save($player);
    }

    private function ensureMarketEnabled(Player $player): void
    {
        $world = $player->getWorld();
        if (!$world->getMarket()) {
            throw new RunTimeException($this->translator->trans('Market not enabled!', [], 'market'));
        }
    }

    private function ensureMarketItemNotOwnedByPlayer(MarketItem $marketItem, Player $player): void
    {
        if ($marketItem->getPlayer()->getId() === $player->getId()) {
            throw new RunTimeException($this->translator->trans('Can not buy or sell to yourself!', [], 'market'));
        }
    }

    private function ensureValidGameResource(string $gameResource): void
    {
        if (!GameResource::isValid($gameResource)) {
            throw new RunTimeException($this->translator->trans('Invalid resource!', [], 'market'));
        }
    }

    private function getMarketItem(Player $player, int $marketItemId): MarketItem
    {
        $marketItem = $this->marketItemRepository->find($marketItemId);

        if ($marketItem === null) {
            throw new RunTimeException($this->translator->trans('Market order does not exist!', [], 'market'));
        }

        if ($marketItem->getWorld()->getId() != $player->getWorld()->getId()) {
            throw new RunTimeException($this->translator->trans('Wrong game world!', [], 'market'));
        }

        return $marketItem;
    }

    private function addGameResources(MarketItem $marketItem, Resources $resources): Resources
    {
        switch ($marketItem->getGameResource()) {
            case GameResource::GAME_RESOURCE_WOOD:
                $resources->setWood($resources->getWood() + $marketItem->getAmount());
                break;
            case GameResource::GAME_RESOURCE_FOOD:
                $resources->setFood($resources->getFood() + $marketItem->getAmount());
                break;
            case GameResource::GAME_RESOURCE_STEEL:
                $resources->setSteel($resources->getSteel() + $marketItem->getAmount());
                break;
            default:
                throw new RunTimeException($this->translator->trans('Unknown resource type!', [], 'market'));
        }

        return $resources;
    }

    private function substractGameResources(MarketItem $marketItem, Resources $resources): Resources
    {
        switch ($marketItem->getGameResource()) {
            case GameResource::GAME_RESOURCE_WOOD:
                $resources->setWood($resources->getWood() - $marketItem->getAmount());
                break;
            case GameResource::GAME_RESOURCE_FOOD:
                $resources->setFood($resources->getFood() - $marketItem->getAmount());
                break;
            case GameResource::GAME_RESOURCE_STEEL:
                $resources->setSteel($resources->getSteel() - $marketItem->getAmount());
                break;
            default:
                throw new RunTimeException($this->translator->trans('Unknown resource type!', [], 'market'));
        }

        return $resources;
    }

    private function substractAndValidateGameResources(
        string $gameResource,
        Resources $resources,
        int $amount
    ): Resources {
        switch ($gameResource) {
            case GameResource::GAME_RESOURCE_WOOD:
                $this->ensureEnoughResources($amount, $resources->getWood(), GameResource::GAME_RESOURCE_WOOD);
                $resources->setWood($resources->getWood() - $amount);
                break;
            case GameResource::GAME_RESOURCE_FOOD:
                $this->ensureEnoughResources($amount, $resources->getFood(), GameResource::GAME_RESOURCE_FOOD);
                $resources->setFood($resources->getFood() - $amount);
                break;
            case GameResource::GAME_RESOURCE_STEEL:
                $this->ensureEnoughResources($amount, $resources->getSteel(), GameResource::GAME_RESOURCE_STEEL);
                $resources->setSteel($resources->getSteel() - $amount);
                break;
            default:
                throw new RunTimeException($this->translator->trans('Unknown resource type!', [], 'market'));
        }

        return $resources;
    }

    private function ensureEnoughResources(int $amount, int $resourceAmount, string $resourceName): void
    {
        if ($amount > $resourceAmount) {
            throw new RunTimeException($this->translator->trans('You do not have enough %ressource%!', ['%ressource%' => $resourceName], 'market'));
        }
    }

    private function createReport(Player $player, string $text): void
    {
        $report = Report::createForPlayer($player, time(), Report::TYPE_MARKET, $text);
        $this->reportRepository->save($report);
    }
}
