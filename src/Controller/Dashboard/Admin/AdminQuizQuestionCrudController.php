<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\QuizQuestion;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdminQuizQuestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return QuizQuestion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Questions du quiz');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('activity', 'Activité');
        yield TextField::new('question', 'Question');
        yield TextField::new('propositionA', 'Proposition A');
        yield TextField::new('propositionB', 'Proposition B');
        yield TextField::new('propositionC', 'Proposition C');
        yield ChoiceField::new('correctAnswer', 'Bonne réponse')
            ->setChoices([
                'Proposition A' => 1,
                'Proposition B' => 2,
                'Proposition C' => 3,
            ]);
    }
}