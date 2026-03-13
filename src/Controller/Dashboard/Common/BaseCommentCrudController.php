<?php

namespace App\Controller\Dashboard\Common;

use App\Entity\Comment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

abstract class BaseCommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Commentaires');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('resource', 'Ressource'))
            ->add(EntityFilter::new('user', 'Auteur'))
            ->add(BooleanFilter::new('isPublished', 'Publié'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextareaField::new('content', 'Contenu');
        yield AssociationField::new('resource', 'Ressource');
        yield AssociationField::new('user', 'Auteur');
        yield BooleanField::new('isPublished', 'Publié');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield TextField::new('reportSummary', 'Signalements')
            ->hideOnForm()
            ->formatValue(function ($value, Comment $comment) {
                $count = $comment->getReportCount();
                if ($count === 0) {
                    return '<span class="badge bg-success">0</span>';
                }
                $detail = implode('<br>', array_map(
                    fn($r) => '<b>' . htmlspecialchars($r['user']) . '</b> : ' . htmlspecialchars($r['motif']),
                    $comment->getReports()
                ));
                return sprintf('<span class="badge bg-danger">⚠ %d</span><br><small>%s</small>', $count, $detail);
            });
    }
}