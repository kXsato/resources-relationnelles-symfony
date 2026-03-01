<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Dom\Text;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use App\Enum\UserRole;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
       return $crud
       ->setPageTitle('index', 'Gérer les utilisateurs')
       ;
    }
    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('username'),
            EmailField::new('email'),
            ChoiceField::new('roles')
                ->setChoices([
                    'Super Admin' => UserRole::ROLE_SUPER_ADMIN->value,
                    'Admin' => UserRole::ROLE_ADMIN->value,
                    'Moderator' => UserRole::ROLE_MODERATOR->value,
                ])
                ->allowMultipleChoices()
                ->renderExpanded(),
            DateTimeField::new('BirthDate'),
            DateTimeField::new('registrationDate'),
            DateTimeField::new('lastLogin'),

        ];
    }
    
}
