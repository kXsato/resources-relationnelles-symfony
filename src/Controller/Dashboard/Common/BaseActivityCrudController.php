<?php

namespace App\Controller\Dashboard\Common;

use App\Entity\Activity;
use App\Entity\User;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Classe de base abstraite pour la gestion des activités.
 *
 * Accessible à tous les utilisateurs connectés (user, moderator, super admin, admin).
 * Centralise la logique : filtrage par auteur, champs, actions.
 *
 * Chaque dashboard hérite de cette classe et fournit son dashboard via getDashboardClass().
 */
abstract class BaseActivityCrudController extends AbstractCrudController
{
    public function __construct(
        protected Security $security,
        protected AdminUrlGenerator $adminUrlGenerator,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Activity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Mes activités');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'))
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $a) => $a
                ->setLabel('Retour à la liste')
                ->setIcon('fas fa-arrow-left')
                ->addCssClass('btn btn-link text-danger'))
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $a) => $a
                ->setLabel('Retour à la liste')
                ->setIcon('fas fa-arrow-left')
                ->addCssClass('btn btn-link text-danger'))
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }

    /**
     * Restreint la liste aux activités dont l'utilisateur connecté est l'auteur.
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        /** @var User $user */
        $user = $this->security->getUser();
        $qb->andWhere('entity.author = :user')
           ->setParameter('user', $user);

        return $qb;
    }

    /**
     * Initialise une nouvelle activité avec l'auteur connecté.
     */
    public function createEntity(string $entityFqcn): Activity
    {
        $activity = new Activity();

        /** @var User $user */
        $user = $this->security->getUser();
        $activity->setAuthor($user);

        return $activity;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titre');
        yield TextField::new('slug', 'Slug')->hideOnIndex();
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield TextareaField::new('content', 'Contenu')->hideOnIndex();
        yield ChoiceField::new('gameType', 'Type de jeu')
            ->setChoices(Activity::GAME_TYPES)
            ->renderExpanded(false)
            ->allowMultipleChoices(false);
        yield DateTimeField::new('startDate', 'Date de début')->hideOnIndex();
        yield DateTimeField::new('endDate', 'Date de fin')->hideOnIndex();
        yield AssociationField::new('categories', 'Catégories');
        yield AssociationField::new('author', 'Auteur')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }

    abstract protected function getDashboardClass(): string;
}