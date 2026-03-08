<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\Common\BasePublishedArticleCrudController;

class AdminPublishedArticleCrudController extends BasePublishedArticleCrudController
{
    protected function getDashboardFqcn(): string
    {
        return AdminDashboardController::class;
    }
}
