<?php

namespace App\Controller\Dashboard\Moderator;

use App\Controller\Dashboard\Common\BaseOwnArticleCrudController;

/**
 * CRUD des articles personnels du modérateur connecté.
 */
class ModeratorOwnArticleCrudController extends BaseOwnArticleCrudController
{
    protected function getDashboardClass(): string
    {
        return ModeratorDashboardController::class;
    }
}
