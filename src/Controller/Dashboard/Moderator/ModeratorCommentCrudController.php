<?php

namespace App\Controller\Dashboard\Moderator;

use App\Controller\Dashboard\Common\BaseCommentCrudController;
use App\Repository\CommentRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

class ModeratorCommentCrudController extends BaseCommentCrudController
{
    private bool $onlyReported = false;

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

        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $filterReported)
            ->add(Crud::PAGE_INDEX, $filterAll)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'));
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