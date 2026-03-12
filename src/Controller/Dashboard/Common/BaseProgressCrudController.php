<?php

namespace App\Controller\Dashboard\Common;

use App\Entity\UserRessourceProgress;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;

abstract class BaseProgressCrudController extends AbstractCrudController
{
    public function __construct(protected Security $security) {}

    public static function getEntityFqcn(): string
    {
        return UserRessourceProgress::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Ressources en cours');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $user = $this->security->getUser();
        $qb->andWhere('entity.UserRessources = :user')
           ->andWhere('entity.status = :status')
           ->setParameter('user', $user)
           ->setParameter('status', 'in_progress');

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('resource', 'Ressource');
        yield IntegerField::new('readPrecentage', 'Progression (%)');
        yield TextField::new('status', 'Statut');
    }
}
