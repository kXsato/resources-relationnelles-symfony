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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

/**
 * CRUD des articles pour le tableau de bord utilisateur.
 *
 * Chaque utilisateur ne voit et ne gère que ses propres articles.
 * Les articles peuvent être sauvegardés en brouillon ou soumis pour relecture (pending).
 */
class UserArticleCrudController extends AbstractCrudController
{
    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
        private AdminUrlGenerator $adminUrlGenerator,
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
     *
     * Les boutons de sauvegarde utilisent le paramètre POST "_save_btn" pour transmettre
     * le status voulu (draft ou pending) sans sortir du pipeline natif d'EasyAdmin.
     *
     * - "Sauvegarder"          → status DRAFT   (_save_btn=draft)
     * - "Soumettre pour relecture" → status PENDING (_save_btn=pending)
     * - "Abandonner"           → retour à la liste sans sauvegarde
     * - "Supprimer"            → suppression de l'article (page édition uniquement)
     */
    public function configureActions(Actions $actions): Actions
    {
        // Bouton "Soumettre pour relecture" : emprunte le pipeline saveAndReturn d'EasyAdmin
        // mais envoie _save_btn=pending pour que persistEntity/updateEntity fixe le bon status.
        $submitForReview = Action::new('submitForReview', 'Soumettre pour relecture', 'fas fa-paper-plane')
            ->displayAsButton()
            ->addCssClass('btn btn-success')
            ->setHtmlAttributes(['name' => '_save_btn', 'value' => ResourceStatus::PENDING->value])
            ->linkToCrudAction(Action::SAVE_AND_RETURN);

        return $actions
            // Suppression des boutons par défaut inutiles
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            // Bouton Supprimer sur la page d'édition uniquement
            ->add(Crud::PAGE_EDIT, Action::DELETE)
            ->update(Crud::PAGE_EDIT, Action::DELETE, fn(Action $a) => $a
                ->setLabel('Supprimer')
                ->setIcon('fas fa-trash')
                ->addCssClass('btn btn-danger'))
            // Renommer SAVE_AND_RETURN en "Sauvegarder" et lui associer _save_btn=draft
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
            // Bouton Abandonner : lien vers la liste, sans soumettre le formulaire
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
     * Redirige toujours vers la liste des articles après une sauvegarde.
     *
     * Override nécessaire car nos boutons utilisent "name=_save_btn" au lieu du
     * paramètre natif "ea[newForm][btn]", ce qui ferait sinon atterrir sur le profil
     * utilisateur (comportement par défaut du dashboard sans contrôleur spécifié).
     */
    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->setDashboard(UserDashboardController::class)
                ->setController(self::class)
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

    /**
     * Lit le paramètre POST "_save_btn" pour déterminer le status voulu.
     * Retourne DRAFT si le paramètre est absent ou invalide.
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
        // Le status est en lecture seule sur le formulaire (géré par les boutons)
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(ResourceStatus::choices())
            ->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}
