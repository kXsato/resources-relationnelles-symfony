<?php

namespace App\Controller\Dashboard\Moderator;

use App\Controller\Dashboard\Common\BasePublishedArticleCrudController;

class ModeratorPublishedArticleCrudController extends BasePublishedArticleCrudController
{
    protected function getDashboardFqcn(): string
    {
        return ModeratorDashboardController::class;
    }
}
