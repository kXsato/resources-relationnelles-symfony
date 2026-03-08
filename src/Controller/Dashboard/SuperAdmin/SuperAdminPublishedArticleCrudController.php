<?php

namespace App\Controller\Dashboard\SuperAdmin;

use App\Controller\Dashboard\Common\BasePublishedArticleCrudController;

class SuperAdminPublishedArticleCrudController extends BasePublishedArticleCrudController
{
    protected function getDashboardFqcn(): string
    {
        return SuperAdminDashboardController::class;
    }
}
