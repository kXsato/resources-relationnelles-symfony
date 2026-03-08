<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\Common\BaseUserCrudController;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class AdminUserCrudController extends BaseUserCrudController
{
    protected function getDashboardFqcn(): string
    {
        return AdminDashboardController::class;
    }

    protected function getToggleRouteName(): string
    {
        return 'admin_toggle_user_account';
    }

    #[Route('/admin/user/{id}/toggle-account', name: 'admin_toggle_user_account')]
    public function toggleAccount(User $user): RedirectResponse
    {
        return $this->toggleAccountAction($user);
    }
}
