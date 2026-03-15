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
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classe de base pour la gestion des articles publiés.
 *
 * Partagée entre le dashboard admin et modérateur.
 * Actions disponibles : consulter, retirer de la publication (→ archivé), supprimer.
 */
abstract class BasePublishedArticleCrudController extends AbstractCrudController
{
    public function __construct(
        protected AdminUrlGenerator $adminUrlGenerator,
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
            ->setPageTitle('index', 'Articles publiés')
            ->setPageTitle('detail', 'Consulter l\'article');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('categories', 'Catégorie'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $unpublish = Action::new('unpublishArticle', 'Retirer de la publication', 'fas fa-eye-slash')
            ->addCssClass('btn btn-warning')
            ->askConfirmation('Retirer cet article de la publication ?')
            ->linkToCrudAction('unpublishArticle');

        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $unpublish)
            ->add(Crud::PAGE_DETAIL, $unpublish)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $a) => $a
                ->setIcon('fas fa-trash'))
            ->update(Crud::PAGE_DETAIL, Action::DELETE, fn(Action $a) => $a
                ->setIcon('fas fa-trash'));
    }

    public function unpublishArticle(AdminContext $context): Response
    {
        $entityId = $context->getRequest()->query->get('entityId');
        /** @var Article $article */
        $article = $this->em->find(Article::class, $entityId);

        if ($article === null) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        $article->setStatus(ResourceStatus::ARCHIVED->value);
        $this->em->flush();

        $this->addFlash('success', sprintf('L\'article "%s" a été retiré de la publication.', $article->getTitle()));

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
           ->setParameter('status', ResourceStatus::PUBLISHED->value);

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titre');
        yield TextField::new('description', 'Description')->hideOnIndex();
        yield Field::new('content', 'Contenu')
            ->hideOnIndex()
            ->setFormTypeOption('attr', ['readonly' => 'readonly', 'rows' => 10]);
        yield AssociationField::new('categories', 'Catégories');
        yield AssociationField::new('author', 'Auteur');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(ResourceStatus::choices())
            ->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le');
        yield DateTimeField::new('updatedAt', 'Modifié le')->hideOnIndex();
        yield AssociationField::new('comments', 'Commentaires')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true);
    }
}
