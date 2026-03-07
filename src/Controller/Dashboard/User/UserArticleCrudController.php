<?php

namespace App\Controller\Dashboard\User;

use App\Controller\Dashboard\Common\BaseOwnArticleCrudController;

/**
 * CRUD des articles personnels de l'utilisateur connecté.
 */
class UserArticleCrudController extends BaseOwnArticleCrudController
{
    protected function getDashboardClass(): string
    {
        return UserDashboardController::class;
    }
}
