<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::DELETE)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->disable(Action::SAVE_AND_RETURN)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $a) => $a->setIcon('fas fa-arrow-left'))
            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $a) => $a->setIcon('fas fa-arrow-left'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Nom'),
            IntegerField::new('resourceCount', 'Nombre de ressources')
                ->setTemplatePath('admin/field/resource_count.html.twig')
                ->hideOnForm(),
        ];
    }
}