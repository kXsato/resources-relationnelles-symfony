<?php

namespace App\Controller\Dashboard\Moderator;

use App\Entity\Article;
use App\Enum\ResourceStatus;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * CRUD des articles en attente de validation pour les modérateurs.
 *
 * Les modérateurs peuvent consulter les articles avec le statut "pending"
 * et modifier uniquement leurs catégories.
 */
class ModeratorArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Articles en attente');
    }

    /**
     * Configure les actions : lecture seule sur la liste et le détail,
     * formulaire d'édition limité aux catégories.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            // Page liste : bouton "Consulter" et bouton "Modifier les catégories"
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $a) => $a
                ->setLabel('Modifier les catégories')
                ->setIcon('fas fa-tags'))
            // Page détail : bouton "Modifier les catégories"
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $a) => $a
                ->setLabel('Modifier les catégories')
                ->setIcon('fas fa-tags'))
            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $a) => $a
                ->setLabel('Retour à la liste')
                ->setIcon('fas fa-arrow-left'))
            // Page édition : renommer le bouton de sauvegarde
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn(Action $a) => $a
                ->setLabel('Enregistrer')
                ->setIcon('fas fa-save'));
    }

    /**
     * Restreint la liste aux articles ayant le statut "pending".
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $qb->andWhere('entity.status = :status')
           ->setParameter('status', ResourceStatus::PENDING->value);

        return $qb;
    }

    /**
     * Champs affichés selon la page :
     * - Liste    : titre, catégories, auteur, statut, date de création
     * - Détail   : tous les champs en lecture seule
     * - Édition  : titre, slug, description, contenu, auteur, dates (lecture seule) + catégories (modifiable)
     */
    public function configureFields(string $pageName): iterable
    {
        $readonly = ['readonly' => 'readonly'];

        yield TextField::new('title', 'Titre')
            ->setFormTypeOption('attr', $readonly);
        yield TextField::new('slug', 'Slug')
            ->hideOnIndex()
            ->setFormTypeOption('attr', $readonly);
        yield TextField::new('description', 'Description')
            ->hideOnIndex()
            ->setFormTypeOption('attr', $readonly);
        // Contenu affiché en lecture seule sur le formulaire (textarea non éditable)
        yield Field::new('content', 'Contenu')
            ->hideOnIndex()
            ->setFormTypeOption('attr', ['readonly' => 'readonly', 'rows' => 10]);
        yield AssociationField::new('categories', 'Catégories');
        // Auteur en lecture seule sur le formulaire
        yield AssociationField::new('author', 'Auteur')
            ->setFormTypeOption('disabled', true);
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(ResourceStatus::choices())
            ->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')
            ->setFormTypeOption('disabled', true);
        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true);
    }
}
