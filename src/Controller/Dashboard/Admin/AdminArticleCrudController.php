<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\Common\BaseArticleValidationCrudController;

class AdminArticleCrudController extends BaseArticleValidationCrudController
{
    protected function getDashboardFqcn(): string
    {
        return AdminDashboardController::class;
    }
}
