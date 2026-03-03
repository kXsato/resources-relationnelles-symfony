<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EmilePerron\TinymceBundle\Form\Type\TinymceType;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class ArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->addFormTheme('@Tinymce/form/tinymce_type.html.twig');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('categories', 'Catégorie'))
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices([
                'Draft' => 'draft',
                'Published' => 'published',
                'Pending' => 'pending',
                'Archived' => 'archived',
                'Rejected' => 'rejected',
            ]));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title','Titre'),
            TextField::new('slug', 'Slug'),
            TextField::new('description', 'Description'),
            DateTimeField::new('createdAt', 'Date de création')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Date de mise à jour')->hideOnForm(),
            Field::new('content', 'Contenu')
            ->hideOnIndex()
            ->setFormType(TinymceType::class),
                
            AssociationField::new('categories', 'Catégories')
                ->setFormTypeOptions(['by_reference' => false]),

            ChoiceField::new('Status', 'Statut')
                ->setChoices([
                    'Draft' => 'draft',
                    'Published' => 'published',
                    'Pending' => 'pending',
                    'Archived' => 'archived',
                    'rejected' => 'rejected',
                ])
                
           

        ];
    }
}
