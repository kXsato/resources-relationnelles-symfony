<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\Common\BaseOwnArticleCrudController;


/**
 * CRUD des articles personnels du modérateur connecté.
 */
class AdminOwnArticleCrudController extends BaseOwnArticleCrudController
{
    protected function getDashboardClass(): string
    {
        return AdminDashboardController::class;
    }
}
