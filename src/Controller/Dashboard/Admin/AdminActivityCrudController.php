<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\Activity;
use App\Entity\User;
use App\Enum\ResourceStatus;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;

class AdminActivityCrudController extends AbstractCrudController
{
    public function __construct(private Security $security) {}

    public static function getEntityFqcn(): string
    {
        return Activity::class;
    }

    public function createEntity(string $entityFqcn): Activity
    {
        $activity = new Activity();

        /** @var User $user */
        $user = $this->security->getUser();
        $activity->setAuthor($user);

        return $activity;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Activités');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'));
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
        yield DateTimeField::new('startDate', 'Date de début');
        yield DateTimeField::new('endDate', 'Date de fin');
        yield IntegerField::new('maxParticipants', 'Participants max');
        yield AssociationField::new('categories', 'Catégories');
        yield AssociationField::new('author', 'Auteur')->hideOnForm();
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(ResourceStatus::choices())
            ->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}