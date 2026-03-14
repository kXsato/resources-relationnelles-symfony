<?php

namespace App\Controller\Dashboard\Common;

use App\Contract\OwnArticleCrudControllerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
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
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EmilePerron\TinymceBundle\Form\Type\TinymceType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Classe de base abstraite pour la gestion des articles personnels d'un utilisateur.
 *
 * Centralise toute la logique commune : filtrage par auteur, boutons d'action,
 * gestion des statuts, protection de l'édition des articles publiés, etc.
 *
 * Chaque dashboard hérite de cette classe et n'a qu'à fournir son propre dashboard
 * via getDashboardClass().
 */
abstract class BaseOwnArticleCrudController extends AbstractCrudController implements OwnArticleCrudControllerInterface
{
    public function __construct(
        protected Security $security,
        protected RequestStack $requestStack,
        protected AdminUrlGenerator $adminUrlGenerator,
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

    /**
     * Configure les actions disponibles sur les pages de liste, création et édition.
     */
    public function configureActions(Actions $actions): Actions
    {
        // Boutons custom : EasyAdmin affiche les boutons (displayAsButton) avant les liens,
        // dans leur ordre d'ajout. L'ordre d'add() détermine l'ordre d'affichage.
        $saveDraft = Action::new('saveDraft', 'Sauvegarder', 'fas fa-save')
            ->displayAsButton()
            ->addCssClass('btn btn-secondary')
            ->setHtmlAttributes(['name' => '_save_btn', 'value' => ResourceStatus::DRAFT->value])
            ->linkToCrudAction(Action::SAVE_AND_RETURN);

        $submitForReview = Action::new('submitForReview', 'Soumettre pour relecture', 'fas fa-paper-plane')
            ->displayAsButton()
            ->addCssClass('btn btn-success')
            ->setHtmlAttributes(['name' => '_save_btn', 'value' => ResourceStatus::PENDING->value])
            ->linkToCrudAction(Action::SAVE_AND_RETURN);

        return $actions
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $a) => $a
                ->displayIf(fn(Article $article) => $article->getStatus() !== ResourceStatus::PUBLISHED->value))
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $a) => $a
                ->setLabel('Retour à la liste')
                ->setIcon('fas fa-arrow-left'))
            // Ordre affiché : Sauvegarder | Soumettre | Supprimer | Retour à la liste
            // Boutons (displayAsButton) en premier, liens ensuite — dans l'ordre d'ajout
            ->add(Crud::PAGE_NEW, $saveDraft)
            ->add(Crud::PAGE_EDIT, $saveDraft)
            ->add(Crud::PAGE_NEW, $submitForReview)
            ->add(Crud::PAGE_EDIT, $submitForReview)
            ->add(Crud::PAGE_EDIT, Action::DELETE)
            ->update(Crud::PAGE_EDIT, Action::DELETE, fn(Action $a) => $a
                ->setLabel('Supprimer')
                ->setIcon('fas fa-trash')
                ->addCssClass('btn btn-danger'))
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $a) => $a
                ->setLabel('Retour à la liste')
                ->setIcon('fas fa-arrow-left')
                ->addCssClass('btn btn-link text-danger'))
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $a) => $a
                ->setLabel('Retour à la liste')
                ->setIcon('fas fa-arrow-left')
                ->addCssClass('btn btn-link text-danger'));
    }

    /**
     * Restreint la liste aux articles dont l'utilisateur connecté est l'auteur.
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
     * Initialise un nouvel article avec l'auteur connecté et le status brouillon par défaut.
     */
    public function createEntity(string $entityFqcn): Article
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $article = new Article();
        $article->setAuthor($user);
        $article->setStatus(ResourceStatus::DRAFT->value);

        return $article;
    }

    /**
     * Bloque l'accès à la page d'édition pour les articles publiés.
     */
    public function edit(AdminContext $context): mixed
    {
        /** @var Article $article */
        $article = $context->getEntity()->getInstance();

        if ($article->getStatus() === ResourceStatus::PUBLISHED->value) {
            $this->addFlash('warning', 'Les articles publiés ne peuvent pas être modifiés.');

            return $this->redirect(
                $this->adminUrlGenerator
                    ->setDashboard($this->getDashboardClass())
                    ->setController(static::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($article->getId())
                    ->generateUrl()
            );
        }

        return parent::edit($context);
    }

    /**
     * Redirige toujours vers la liste des articles après une sauvegarde.
     */
    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->setDashboard($this->getDashboardClass())
                ->setController(static::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    /**
     * Applique le status issu du paramètre POST "_save_btn" avant la persistance.
     */
    public function persistEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $entityInstance->setStatus($this->getIntendedStatus()->value);
        parent::persistEntity($em, $entityInstance);
    }

    /**
     * Applique le status issu du paramètre POST "_save_btn" avant la mise à jour.
     */
    public function updateEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $entityInstance->setStatus($this->getIntendedStatus()->value);
        parent::updateEntity($em, $entityInstance);
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
        yield AssociationField::new('comments', 'Commentaires')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true);
    }

    /**
     * Retourne la classe du dashboard associé à ce contrôleur.
     */
    abstract protected function getDashboardClass(): string;

    /**
     * Lit le paramètre POST "_save_btn" pour déterminer le status voulu.
     *
     * - Bouton "Sauvegarder"              → _save_btn=draft    → DRAFT
     * - Bouton "Soumettre pour relecture" → _save_btn=pending  → PENDING
     *
     * Retourne DRAFT si le paramètre est absent ou invalide.
     */
    private function getIntendedStatus(): ResourceStatus
    {
        $value = $this->requestStack->getCurrentRequest()?->request->get('_save_btn');

        return ResourceStatus::tryFrom($value) ?? ResourceStatus::DRAFT;
    }
}
