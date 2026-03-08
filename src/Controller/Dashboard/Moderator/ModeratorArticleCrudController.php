<?php

namespace App\Controller\Dashboard\Moderator;

use App\Controller\Dashboard\Common\BaseArticleValidationCrudController;

//TODO: Afficher une alerte sur le tableau de bord du modérateur s'il y a des articles en attente depuis plus de 48h pour éviter les délais de modération trop longs.
//TODO: Ajouter une section "Commentaires" pour permettre aux modérateurs de valider ou supprimer les commentaires signalés.
class ModeratorArticleCrudController extends BaseArticleValidationCrudController
{
    protected function getDashboardFqcn(): string
    {
        return ModeratorDashboardController::class;
    }
}
