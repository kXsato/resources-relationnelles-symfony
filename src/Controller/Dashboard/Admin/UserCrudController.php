<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator,
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        $qb->andWhere('entity.id != :currentUser')
           ->setParameter('currentUser', $currentUser->getId());

        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        $toggleAccount = Action::new('toggleAccount', false)
            ->setIcon('fas fa-power-off')
            ->addCssClass('btn btn-sm')
            ->setHtmlAttributes(['title' => 'Activer / Désactiver le compte'])
            ->displayIf(fn (User $user) => !in_array('ROLE_ADMIN', $user->getRoles()))
            ->linkToRoute(
                'admin_toggle_user_account',
                fn (User $user) => ['id' => $user->getId()]
            )
            ->setTemplatePath('admin/user/toggle_action.html.twig');

        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $toggleAccount)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'));
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('index', 'Gérer les utilisateurs');
    }

    public function configureFields(string $pageName): iterable
    {
        $readonly = ['readonly' => 'readonly'];

        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('username')
                ->setLabel('Pseudo')
                ->setFormTypeOption('attr', $readonly),
            EmailField::new('email')
                ->setLabel('adresse mail')
                ->setFormTypeOption('attr', $readonly),
            DateTimeField::new('BirthDate')
                ->setLabel('Date de naissance')
                ->setFormTypeOption('attr', $readonly),
            DateTimeField::new('registrationDate')
                ->setLabel('Date de création du compte')
                ->setFormTypeOption('attr', $readonly),
            DateTimeField::new('lastLogin')
                ->setLabel('Date de dernière connection')
                ->setFormTypeOption('attr', $readonly),
            TextField::new('accountStatus')
                ->setLabel('Compte actif')
                ->onlyOnDetail(),
        ];
    }

    #[Route('/admin/user/{id}/toggle-account', name: 'admin_toggle_user_account')]
    public function toggleAccount(User $user): RedirectResponse
    {
        $user->setIsAccountActivated(!$user->isAccountActivated());
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            sprintf(
                'Le compte de "%s" a été %s.',
                $user->getUserName(),
                $user->isAccountActivated() ? 'réactivé' : 'désactivé'
            )
        );

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($user->getId())
                ->generateUrl()
        );
    }
}
