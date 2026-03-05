<?php

namespace App\Controller\Dashboard\Moderator;

use App\Entity\Article;
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
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;



//TODO: Afficher une alerte sur le tableau de bord du modérateur s'il y a des articles en attente depuis plus de 48h pour éviter les délais de modération trop longs.
//TODO: Ajouter une section "Commentaires" pour permettre aux modérateurs de valider ou supprimer les commentaires signalés.
/**
 * CRUD des articles en attente de validation pour les modérateurs.
 *
 * Les modérateurs peuvent consulter les articles avec le statut "pending",
 * modifier leurs catégories, et les valider (status → published).
 */
class ModeratorArticleCrudController extends AbstractCrudController
{
    public function __construct(
        private RequestStack $requestStack,
        private AdminUrlGenerator $adminUrlGenerator,
        private Security $security,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Articles en attente')
            ->setPageTitle('edit', 'Consulter l\'article');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('categories', 'Catégorie'))
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices(ResourceStatus::choices()));
    }

    /**
     * Configure les actions disponibles.
     *
     * - "Enregistrer"  → sauvegarde les catégories sans changer le statut (_save_btn absent)
     * - "Valider"      → publie l'article (_save_btn=published)
     * - "Retour"       → retour à la liste sans sauvegarde
     */
    public function configureActions(Actions $actions): Actions
    {
        $currentUser = $this->security->getUser();

        $validate = Action::new('validate', 'Valider', 'fas fa-check')
            ->displayAsButton()
            ->askConfirmation('Êtes-vous sûr de vouloir valider cet article ?')
            ->addCssClass('btn btn-success')
            ->setHtmlAttributes(['name' => '_save_btn', 'value' => ResourceStatus::PUBLISHED->value])
            ->linkToCrudAction(Action::SAVE_AND_RETURN)
            // Un modérateur ne peut pas valider ses propres articles
            ->displayIf(fn(Article $article) => $article->getAuthor()?->getId() !== $currentUser?->getId());

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
            // Page édition : "Valider", "Retour à la liste"
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $a) => $a
                ->setLabel('Retour à la liste')
                ->setIcon('fas fa-arrow-left')
                ->addCssClass('btn btn-link'))
            ->add(Crud::PAGE_EDIT, $validate);
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
     * Change le statut uniquement si le bouton "Valider" a été cliqué (_save_btn=published).
     * Sinon, conserve le statut existant (sauvegarde des catégories uniquement).
     */
    public function updateEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $value = $this->requestStack->getCurrentRequest()?->request->get('_save_btn');
        $newStatus = ResourceStatus::tryFrom($value);

        if ($newStatus !== null) {
            $entityInstance->setStatus($newStatus->value);
        }

        parent::updateEntity($em, $entityInstance);
    }

    /**
     * Redirige toujours vers la liste des articles en attente après une sauvegarde.
     */
    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->setDashboard(ModeratorDashboardController::class)
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
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
