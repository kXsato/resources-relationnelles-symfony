<?php

namespace App\Security\Voter;

use App\Entity\Resource;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ResourceVoter extends Voter
{
    public const VIEW = 'RESOURCE_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof Resource;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var Resource $subject */
        if (!$subject->isAdultOnly()) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $birthDate = $user->getBirthDate();
        if ($birthDate === null) {
            return false;
        }

        return $birthDate->diff(new \DateTimeImmutable())->y >= 18;
    }
}
