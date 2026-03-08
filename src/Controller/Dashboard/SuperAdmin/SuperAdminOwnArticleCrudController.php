<?php

namespace App\Controller\Dashboard\SuperAdmin;

use App\Controller\Dashboard\Common\BaseOwnArticleCrudController;

class SuperAdminOwnArticleCrudController extends BaseOwnArticleCrudController
{
    protected function getDashboardClass(): string
    {
        return SuperAdminDashboardController::class;
    }
}
