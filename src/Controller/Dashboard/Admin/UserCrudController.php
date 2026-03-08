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
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'));
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Gérer les utilisateurs');
    }

    public function configureFields(string $pageName): iterable
    {
        $readonly = ['readonly' => 'readonly'];

        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('username')
                ->setFormTypeOption('attr', $readonly),
            EmailField::new('email')
                ->setFormTypeOption('attr', $readonly),
            DateTimeField::new('BirthDate')
                ->setFormTypeOption('attr', $readonly),
            DateTimeField::new('registrationDate')
                ->setFormTypeOption('attr', $readonly),
            DateTimeField::new('lastLogin')
                ->setFormTypeOption('attr', $readonly),

        ];
    }
    
}
