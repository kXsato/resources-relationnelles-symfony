<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\Common\BaseOwnProfileCrudController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AdminProfileCrudController extends BaseOwnProfileCrudController
{
    protected function getDeleteAccountRouteName(): string
    {
        return 'admin_delete_own_account';
    }

    #[Route('/admin/supprimer-mon-compte', name: 'admin_delete_own_account', methods: ['POST'])]
    public function deleteOwnAccount(Request $request): RedirectResponse
    {
        return $this->deleteOwnAccountAction($request);
    }
}
