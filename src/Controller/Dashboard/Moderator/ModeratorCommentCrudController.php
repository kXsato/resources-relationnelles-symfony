<?php

namespace App\Controller\Dashboard\Moderator;

use App\Controller\Dashboard\Common\BaseCommentCrudController;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ModeratorCommentCrudController extends BaseCommentCrudController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdminUrlGenerator $adminUrlGenerator
    ) {}

    public function configureActions(Actions $actions): Actions
    {
        $filterReported = Action::new('filterReported', '🚩 Commentaires signalés')
            ->linkToUrl(function() {
                return '?' . http_build_query(array_merge($_GET, ['onlyReported' => '1']));
            })
            ->createAsGlobalAction()
            ->addCssClass('btn btn-warning');

        $filterAll = Action::new('filterAll', '📋 Tous les commentaires')
            ->linkToUrl(function() {
                return '?' . http_build_query(array_merge($_GET, ['onlyReported' => '0']));
            })
            ->createAsGlobalAction()
            ->addCssClass('btn btn-secondary');

        $resetReports = Action::new('resetReports', 'Supprimer le signalement', 'fas fa-flag')
            ->linkToCrudAction('resetReportsAction')
            ->displayIf(fn(Comment $comment) => $comment->getReportCount() > 0);

        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $resetReports)
            ->add(Crud::PAGE_INDEX, $filterReported)
            ->add(Crud::PAGE_INDEX, $filterAll)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'));
    }

    public function resetReportsAction(AdminContext $context, Request $request): RedirectResponse
    {
        $id = $request->query->get('entityId') ?? $request->request->get('entityId');
        
        if ($id) {
            $comment = $this->em->find(Comment::class, $id);
            if ($comment instanceof Comment) {
                $comment->resetReports();
                $this->em->flush();
                $this->addFlash('success', '✅ Signalements supprimés.');
            }
        } else {
            $this->addFlash('danger', 'Commentaire introuvable.');
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if (isset($_GET['onlyReported']) && $_GET['onlyReported'] === '1') {
            $qb->andWhere("entity.reports != '[]'")
               ->andWhere('entity.reports IS NOT NULL');
        }

        return $qb;
    }
}