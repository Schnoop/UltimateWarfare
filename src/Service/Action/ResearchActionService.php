<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\Player;
use FrankProjects\UltimateWarfare\Entity\Research;
use FrankProjects\UltimateWarfare\Entity\ResearchPlayer;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use FrankProjects\UltimateWarfare\Repository\ResearchPlayerRepository;
use FrankProjects\UltimateWarfare\Repository\ResearchRepository;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResearchActionService
{
    private PlayerRepository $playerRepository;
    private ResearchRepository $researchRepository;
    private ResearchPlayerRepository $researchPlayerRepository;
    private TranslatorInterface $translator;

    public function __construct(
        ResearchRepository $researchRepository,
        ResearchPlayerRepository $researchPlayerRepository,
        PlayerRepository $playerRepository,
        TranslatorInterface $translator
    ) {
        $this->researchRepository = $researchRepository;
        $this->researchPlayerRepository = $researchPlayerRepository;
        $this->playerRepository = $playerRepository;
        $this->translator = $translator;
    }

    public function performResearch(int $researchId, Player $player): void
    {
        $research = $this->getResearchById($researchId);

        $this->ensureCanResearch($research, $player);

        $researchPlayer = new ResearchPlayer();
        $researchPlayer->setPlayer($player);
        $researchPlayer->setResearch($research);
        $researchPlayer->setTimestamp(time());

        $resources = $player->getResources();
        $resources->setCash($resources->getCash() - $research->getCost());

        $player->setResources($resources);
        $this->playerRepository->save($player);
        $this->researchPlayerRepository->save($researchPlayer);
    }

    public function performCancel(int $researchId, Player $player): void
    {
        $research = $this->getResearchById($researchId);

        /** @var ResearchPlayer $playerResearch */
        foreach ($player->getPlayerResearch() as $playerResearch) {
            if ($playerResearch->getResearch()->getId() !== $research->getId()) {
                continue;
            }

            if ($playerResearch->getActive()) {
                throw new RuntimeException($this->translator->trans('Research project is already completed!', [], 'research'));
            }

            $this->researchPlayerRepository->remove($playerResearch);
        }
    }

    private function getResearchById(int $researchId): Research
    {
        $research = $this->researchRepository->find($researchId);

        if ($research === null) {
            throw new RuntimeException($this->translator->trans('This technology does not exist!', [], 'research'));
        }

        if (!$research->getActive()) {
            throw new RuntimeException($this->translator->trans('This technology is disabled!', [], 'research'));
        }

        return $research;
    }

    private function ensureCanResearch(Research $research, Player $player): void
    {
        $researchArray = [];

        /** @var ResearchPlayer $playerResearch */
        foreach ($player->getPlayerResearch() as $playerResearch) {
            if (!$playerResearch->getActive()) {
                throw new RuntimeException($this->translator->trans('You can only research 1 technology at a time!', [], 'research'));
            }

            if ($playerResearch->getResearch()->getId() === $research->getId()) {
                throw new RuntimeException($this->translator->trans('This technology has already been researched!', [], 'research'));
            }

            $researchArray[$playerResearch->getResearch()->getId()] = $playerResearch->getResearch();
        }

        foreach ($research->getResearchNeeds() as $researchNeed) {
            if (!isset($researchArray[$researchNeed->getRequiredResearch()->getId()])) {
                throw new RuntimeException($this->translator->trans('You do not have all required technologies!', [], 'research'));
            }
        }

        if ($research->getCost() > $player->getResources()->getCash()) {
            throw new RuntimeException($this->translator->trans('You can not afford that!', [], 'research'));
        }
    }
}
