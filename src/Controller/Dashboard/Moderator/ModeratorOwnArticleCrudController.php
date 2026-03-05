<?php

namespace App\Controller\Dashboard\Moderator;

use App\Controller\Dashboard\User\UserArticleCrudController;

/**
 * CRUD des articles personnels du modérateur.
 *
 * Hérite de UserArticleCrudController pour réutiliser toute la logique
 * (filtrage par auteur, boutons Sauvegarder / Soumettre / Abandonner, etc.)
 * en pointant vers le dashboard modérateur au lieu du dashboard utilisateur.
 */
class ModeratorOwnArticleCrudController extends UserArticleCrudController
{
    protected function getDashboardClass(): string
    {
        return ModeratorDashboardController::class;
    }
}
