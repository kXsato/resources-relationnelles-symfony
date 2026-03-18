<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class AgeVerificationService
{
    public function __construct(private readonly Security $security) {}

    public function isAdult(User $user): bool
    {
        $birthDate = $user->getBirthDate();
        if ($birthDate === null) {
            return false;
        }

        return $birthDate->diff(new \DateTimeImmutable())->y >= 18;
    }

    public function isCurrentUserAdult(): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $this->isAdult($user);
    }
}
