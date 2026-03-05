<?php

namespace App\Controller\Dashboard\User;

use App\Entity\Article;
use App\Entity\User;
use App\Enum\ResourceStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EmilePerron\TinymceBundle\Form\Type\TinymceType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class UserArticleCrudController extends AbstractCrudController
{
    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Mes articles')
            ->addFormTheme('@Tinymce/form/tinymce_type.html.twig');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('categories', 'Catégorie'))
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(ResourceStatus::choices()));
    }

    public function configureActions(Actions $actions): Actions
    {
        // Bouton "Soumettre" : soumet vers saveAndReturn (même pipeline que Sauvegarder)
        // mais avec _save_btn=pending dans le POST pour différencier dans persistEntity/updateEntity
        $submitForReview = Action::new('submitForReview', 'Soumettre pour relecture', 'fas fa-paper-plane')
            ->displayAsButton()
            ->addCssClass('btn btn-success')
            ->setHtmlAttributes(['name' => '_save_btn', 'value' => ResourceStatus::PENDING->value])
            ->linkToCrudAction(Action::SAVE_AND_RETURN);

        return $actions
            ->disable(Action::DELETE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            // Renommer SAVE_AND_RETURN en "Sauvegarder" avec _save_btn=draft
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, fn(Action $a) => $a
                ->setLabel('Sauvegarder')
                ->setIcon('fas fa-save')
                ->addCssClass('btn btn-secondary')
                ->setHtmlAttributes(['name' => '_save_btn', 'value' => ResourceStatus::DRAFT->value]))
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn(Action $a) => $a
                ->setLabel('Sauvegarder')
                ->setIcon('fas fa-save')
                ->addCssClass('btn btn-secondary')
                ->setHtmlAttributes(['name' => '_save_btn', 'value' => ResourceStatus::DRAFT->value]))
            // Bouton Abandonner (lien simple vers la liste, sans sauvegarde)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $a) => $a
                ->setLabel('Abandonner')
                ->setIcon('fas fa-times')
                ->addCssClass('btn btn-link text-danger'))
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $a) => $a
                ->setLabel('Abandonner')
                ->setIcon('fas fa-times')
                ->addCssClass('btn btn-link text-danger'))
            ->add(Crud::PAGE_NEW, $submitForReview)
            ->add(Crud::PAGE_EDIT, $submitForReview);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        /** @var User $user */
        $user = $this->security->getUser();
        $qb->andWhere('entity.author = :user')
           ->setParameter('user', $user);

        return $qb;
    }

    public function createEntity(string $entityFqcn): Article
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $article = new Article();
        $article->setAuthor($user);
        $article->setStatus(ResourceStatus::DRAFT->value);

        return $article;
    }

    public function persistEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $entityInstance->setStatus($this->getIntendedStatus()->value);
        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $entityInstance->setStatus($this->getIntendedStatus()->value);
        parent::updateEntity($em, $entityInstance);
    }

    /**
     * Lit le paramètre POST "_save_btn" pour déterminer le status voulu.
     * Défaut : DRAFT si le paramètre est absent ou invalide.
     */
    private function getIntendedStatus(): ResourceStatus
    {
        $value = $this->requestStack->getCurrentRequest()?->request->get('_save_btn');

        return ResourceStatus::tryFrom($value) ?? ResourceStatus::DRAFT;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titre');
        yield TextField::new('slug', 'Slug')->hideOnIndex();
        yield TextField::new('description', 'Description')->hideOnIndex();
        yield Field::new('content', 'Contenu')
            ->hideOnIndex()
            ->setFormType(TinymceType::class);
        yield AssociationField::new('categories', 'Catégories');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(ResourceStatus::choices())
            ->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}
