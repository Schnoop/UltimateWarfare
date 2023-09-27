<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Service\Action;

use FrankProjects\UltimateWarfare\Entity\User;
use FrankProjects\UltimateWarfare\Repository\UserRepository;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserActionService
{
    private UserRepository $userRepository;
    private TranslatorInterface $translator;

    public function __construct(
        UserRepository $userRepository,
        TranslatorInterface $translator
    ) {
        $this->userRepository = $userRepository;
        $this->translator = $translator;
    }

    public function addRoleToUser(User $user, string $role): void
    {
        if ($user->hasRole($role)) {
            throw new RuntimeException($this->translator->trans('User already has this role', [], 'user'));
        }

        $user->addRole($role);
        $this->userRepository->save($user);
    }

    public function removeRoleFromUser(User $user, string $role): void
    {
        if (!$user->hasRole($role)) {
            throw new RuntimeException($this->translator->trans('User does not have this role', [], 'user'));
        }

        $user->removeRole($role);
        $this->userRepository->save($user);
    }
}
