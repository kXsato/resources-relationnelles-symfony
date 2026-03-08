<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Suppression d'un compte utilisateur conforme RGPD.
 *
 * Avant suppression, toutes les ressources de l'utilisateur sont anonymisées
 * (author = null) afin de conserver le contenu tout en effaçant le lien
 * avec la personne physique.
 */
class UserDeletionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function delete(User $user): void
    {
        foreach ($user->getResources() as $resource) {
            $resource->setAuthor(null);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
