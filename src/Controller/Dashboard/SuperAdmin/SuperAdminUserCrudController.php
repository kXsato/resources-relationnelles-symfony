<?php

namespace App\Controller\Dashboard\SuperAdmin;

use App\Controller\Dashboard\Common\BaseUserCrudController;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class SuperAdminUserCrudController extends BaseUserCrudController
{
    protected function getDashboardFqcn(): string
    {
        return SuperAdminDashboardController::class;
    }

    protected function getToggleRouteName(): string
    {
        return 'super_admin_toggle_user_account';
    }

    #[Route('/super-admin/user/{id}/toggle-account', name: 'super_admin_toggle_user_account')]
    public function toggleAccount(User $user): RedirectResponse
    {
        return $this->toggleAccountAction($user);
    }
}
