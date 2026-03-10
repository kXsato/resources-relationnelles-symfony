<?php

namespace App\Controller\Dashboard\SuperAdmin;

use App\Controller\Dashboard\Common\BaseOwnProfileCrudController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SuperAdminProfileCrudController extends BaseOwnProfileCrudController
{
    protected function getDeleteAccountRouteName(): string
    {
        return 'super_admin_delete_own_account';
    }

    #[Route('/super-admin/supprimer-mon-compte', name: 'super_admin_delete_own_account', methods: ['POST'])]
    public function deleteOwnAccount(Request $request): RedirectResponse
    {
        return $this->deleteOwnAccountAction($request);
    }
}
