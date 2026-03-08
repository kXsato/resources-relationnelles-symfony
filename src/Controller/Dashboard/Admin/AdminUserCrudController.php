<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\Common\BaseUserCrudController;

class AdminUserCrudController extends BaseUserCrudController
{
    protected function getDashboardFqcn(): string
    {
        return AdminDashboardController::class;
    }
}
