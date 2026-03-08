<?php

namespace App\Controller\Dashboard\SuperAdmin;

use App\Controller\Dashboard\Common\BaseArticleValidationCrudController;

class SuperAdminArticleCrudController extends BaseArticleValidationCrudController
{
    protected function getDashboardFqcn(): string
    {
        return SuperAdminDashboardController::class;
    }
}
