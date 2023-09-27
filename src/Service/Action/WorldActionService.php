<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\World;
use FrankProjects\UltimateWarfare\Repository\FederationRepository;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use FrankProjects\UltimateWarfare\Repository\WorldRepository;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WorldActionService
{
    private WorldRepository $worldRepository;
    private PlayerRepository $playerRepository;
    private FederationRepository $federationRepository;
    private TranslatorInterface $translator;

    public function __construct(
        WorldRepository $worldRepository,
        PlayerRepository $playerRepository,
        FederationRepository $federationRepository,
        TranslatorInterface $translator
    ) {
        $this->worldRepository = $worldRepository;
        $this->playerRepository = $playerRepository;
        $this->federationRepository = $federationRepository;
        $this->translator = $translator;
    }

    public function remove(int $worldId): void
    {
        $world = $this->getWorld($worldId);

        if (count($world->getPlayers()) > 0) {
            throw new RuntimeException($this->translator->trans('World has active players, can not remove!', [], 'world'));
        }

        $this->reset($worldId);
        $this->worldRepository->remove($world);
    }

    /**
     * @param int $worldId
     */
    public function reset(int $worldId): void
    {
        $world = $this->getWorld($worldId);

        foreach ($world->getPlayers() as $player) {
            $this->playerRepository->remove($player);
        }

        foreach ($world->getFederations() as $federation) {
            $this->federationRepository->remove($federation);
        }
    }

    private function getWorld(int $worldId): World
    {
        $world = $this->worldRepository->find($worldId);
        if ($world === null) {
            throw new RuntimeException($this->translator->trans('World does not exist!', [], 'world'));
        }

        return $world;
    }
}
