<?php

namespace App\Controller\Dashboard\Moderator;

use App\Controller\Dashboard\Common\BaseOwnProfileCrudController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ModeratorProfileCrudController extends BaseOwnProfileCrudController
{
    protected function getDeleteAccountRouteName(): string
    {
        return 'moderator_delete_own_account';
    }

    #[Route('/moderator/supprimer-mon-compte', name: 'moderator_delete_own_account', methods: ['POST'])]
    public function deleteOwnAccount(Request $request): RedirectResponse
    {
        return $this->deleteOwnAccountAction($request);
    }
}
