<?php

namespace App\Controller\Dashboard\User;

use App\Controller\Dashboard\Common\BaseProgressCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class UserCompletedProgressCrudController extends BaseProgressCrudController
{
    protected function getStatusFilter(): string
    {
        return 'completed';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Mes lectures terminées');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('resource', 'Ressource');
        yield DateTimeField::new('completeAt', 'Terminée le');
    }
}
