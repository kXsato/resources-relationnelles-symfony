<?php

namespace App\Controller\Dashboard\SuperAdmin;

use App\Controller\Dashboard\Common\BaseProgressCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class SuperAdminProgressCrudController extends BaseProgressCrudController
{
    protected function getStatusFilter(): string
    {
        return 'in_progress';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Mes lectures en cours');
    }
}
