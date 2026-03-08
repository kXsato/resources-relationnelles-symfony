<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use App\Enum\UserRole;
use Symfony\Bundle\SecurityBundle\Security;

class UserCrudController extends AbstractCrudController
{
    public function __construct(private Security $security) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $qb->andWhere('entity.id != :currentUser')
           ->setParameter('currentUser', $currentUser->getId());

        return $qb;
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
