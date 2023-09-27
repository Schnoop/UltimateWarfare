<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Controller\Game;

use FrankProjects\UltimateWarfare\Form\ConfirmPasswordType;
use FrankProjects\UltimateWarfare\Repository\PlayerRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Translation\TranslatableMessage;

final class SurrenderController extends BaseGameController
{
    public function surrender(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        PlayerRepository $playerRepository
    ): Response {
        $player = $this->getPlayer();
        $user = $this->getGameUser();
        $confirmPasswordForm = $this->createForm(ConfirmPasswordType::class, $user);
        $confirmPasswordForm->handleRequest($request);

        if ($confirmPasswordForm->isSubmitted() && $confirmPasswordForm->isValid()) {
            $plainPassword = $confirmPasswordForm->get('plainPassword')->getData();

            if ($passwordHasher->isPasswordValid($user, $plainPassword)) {
                $playerRepository->remove($player);
                $this->addFlash('success', new TranslatableMessage('You have surrendered your empire...', [], 'surrender'));
                return $this->redirectToRoute('Game/Account');
            } else {
                $this->addFlash('error', new TranslatableMessage('Wrong password!', [], 'surrender'));
            }
        }

        return $this->render(
            'game/surrender.html.twig',
            [
                'player' => $this->getPlayer(),
                'canSurrender' => $player->canSurrender(),
                'confirmPasswordForm' => $confirmPasswordForm->createView()
            ]
        );
    }
}
