<?php

namespace App\Controller\Dashboard\User;

use App\Controller\Dashboard\Common\BaseOwnProfileCrudController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class UserProfileCrudController extends BaseOwnProfileCrudController
{
    protected function getDeleteAccountRouteName(): string
    {
        return 'user_delete_own_account';
    }

    #[Route('/mon-compte/supprimer-mon-compte', name: 'user_delete_own_account', methods: ['POST'])]
    public function deleteOwnAccount(Request $request): RedirectResponse
    {
        return $this->deleteOwnAccountAction($request);
    }
}
