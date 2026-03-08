<?php

namespace App\Controller\Dashboard\Common;

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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classe de base pour la gestion et la validation des articles.
 *
 * Partagée entre le dashboard admin et modérateur.
 * Chaque sous-classe déclare son dashboard via getDashboardFqcn().
 */
abstract class BaseArticleValidationCrudController extends AbstractCrudController
{
    public function __construct(
        protected RequestStack $requestStack,
        protected AdminUrlGenerator $adminUrlGenerator,
        protected Security $security,
        protected EntityManagerInterface $em,
    ) {}

    abstract protected function getDashboardFqcn(): string;

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

    public function configureActions(Actions $actions): Actions
    {
        $currentUser = $this->security->getUser();

        $validate = Action::new('validate', 'Valider', 'fas fa-check')
            ->displayAsButton()
            ->askConfirmation('Êtes-vous sûr de vouloir valider cet article ?')
            ->addCssClass('btn btn-success')
            ->setHtmlAttributes(['name' => '_save_btn', 'value' => ResourceStatus::PUBLISHED->value])
            ->linkToCrudAction(Action::SAVE_AND_RETURN)
            ->displayIf(fn(Article $article) => $article->getAuthor()?->getId() !== $currentUser?->getId());

        $validateDetail = Action::new('validateArticle', 'Valider', 'fas fa-check')
            ->addCssClass('btn btn-success')
            ->askConfirmation('Êtes-vous sûr de vouloir valider cet article ?')
            ->linkToCrudAction('validateArticle')
            ->displayIf(fn(Article $article) => $article->getAuthor()?->getId() !== $currentUser?->getId());

        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'))
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_DETAIL, $validateDetail)
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $a) => $a
                ->setLabel('Modifier les catégories')
                ->setIcon('fas fa-tags'))
            ->remove(Crud::PAGE_DETAIL, Action::INDEX)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $a) => $a
                ->setLabel('Retour à la liste')
                ->setIcon('fas fa-arrow-left')
                ->addCssClass('btn btn-link'))
            ->add(Crud::PAGE_EDIT, $validate);
    }

    public function validateArticle(AdminContext $context): Response
    {
        $entityId = $context->getRequest()->query->get('entityId');
        /** @var Article $article */
        $article = $this->em->find(Article::class, $entityId);

        if ($article === null) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        if ($article->getAuthor()?->getUserIdentifier() === $this->security->getUser()?->getUserIdentifier()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas valider votre propre article.');
        }

        $article->setStatus(ResourceStatus::PUBLISHED->value);
        $this->em->flush();

        return $this->redirect(
            $this->adminUrlGenerator
                ->setDashboard($this->getDashboardFqcn())
                ->setController(static::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $qb->andWhere('entity.status = :status')
           ->setParameter('status', ResourceStatus::PENDING->value)
           ->andWhere('entity.author != :currentUser')
           ->setParameter('currentUser', $this->security->getUser());

        return $qb;
    }

    public function updateEntity(EntityManagerInterface $em, mixed $entityInstance): void
    {
        $value = $this->requestStack->getCurrentRequest()?->request->get('_save_btn');
        $newStatus = ResourceStatus::tryFrom($value);

        if ($newStatus !== null) {
            $entityInstance->setStatus($newStatus->value);
        }

        parent::updateEntity($em, $entityInstance);
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->setDashboard($this->getDashboardFqcn())
                ->setController(static::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

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
        yield Field::new('content', 'Contenu')
            ->hideOnIndex()
            ->setFormTypeOption('attr', ['readonly' => 'readonly', 'rows' => 10]);
        yield AssociationField::new('categories', 'Catégories');
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
