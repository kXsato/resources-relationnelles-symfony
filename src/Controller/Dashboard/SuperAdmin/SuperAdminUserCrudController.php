<?php

namespace App\Controller\Dashboard\SuperAdmin;

use App\Controller\Dashboard\Common\BaseUserCrudController;
use App\Entity\User;
use App\Enum\UserRole;
use App\Service\UserDeletionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class SuperAdminUserCrudController extends BaseUserCrudController
{
    public function __construct(
        \Symfony\Bundle\SecurityBundle\Security $security,
        EntityManagerInterface $entityManager,
        \EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator $adminUrlGenerator,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserDeletionService $userDeletionService,
    ) {
        parent::__construct($security, $entityManager, $adminUrlGenerator);
    }

    protected function getDashboardFqcn(): string
    {
        return SuperAdminDashboardController::class;
    }

    protected function getToggleRouteName(): string
    {
        return 'super_admin_toggle_user_account';
    }

    protected function applyUserFilters(QueryBuilder $qb, User $currentUser): void
    {
        // Le super-admin voit tous les utilisateurs sauf lui-même
        $qb->andWhere('entity.id != :currentUser')
           ->setParameter('currentUser', $currentUser->getId());
    }

    public function configureActions(Actions $actions): Actions
    {
        $toggleAccount = Action::new('toggleAccount', false)
            ->setIcon('fas fa-power-off')
            ->addCssClass('btn btn-sm')
            ->setHtmlAttributes(['title' => 'Activer / Désactiver le compte'])
            ->linkToRoute(
                $this->getToggleRouteName(),
                fn (User $user) => ['id' => $user->getId()]
            )
            ->setTemplatePath('admin/user/toggle_action.html.twig');

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $toggleAccount)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $a) => $a
                ->setLabel('Consulter')
                ->setIcon('fas fa-eye'))
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $a) => $a
                ->setLabel('Modifier le rôle')
                ->setIcon('fas fa-user-shield'))
            ->update(Crud::PAGE_DETAIL, Action::DELETE, fn(Action $a) => $a
                ->setLabel('Supprimer le compte (RGPD)')
                ->setIcon('fas fa-user-slash'));
    }

    public function configureFields(string $pageName): iterable
    {
        $readonly = ['readonly' => 'readonly'];
        $isEdit = $pageName === Crud::PAGE_EDIT;

        yield IdField::new('id')->hideOnForm();

        yield TextField::new('username')
            ->setLabel('Pseudo')
            ->setFormTypeOption('attr', $isEdit ? $readonly : []);

        yield EmailField::new('email')
            ->setLabel('Adresse mail')
            ->setFormTypeOption('attr', $isEdit ? $readonly : []);

        yield ChoiceField::new('role', 'Rôle')
            ->setChoices([
                'Utilisateur'          => 'ROLE_USER',
                'Modérateur'           => UserRole::ROLE_MODERATOR->value,
                'Administrateur'       => UserRole::ROLE_ADMIN->value,
                'Super Administrateur' => UserRole::ROLE_SUPER_ADMIN->value,
            ])
            ->renderExpanded()
            ->setFormTypeOption('placeholder', false);

        yield TextField::new('plainPassword', 'Mot de passe')
            ->setFormType(PasswordType::class)
            ->onlyWhenCreating()
            ->setRequired(true);

        yield DateTimeField::new('birthDate')
            ->setLabel('Date de naissance')
            ->setFormTypeOption('attr', $isEdit ? $readonly : []);

        yield DateTimeField::new('registrationDate')
            ->setLabel('Date de création du compte')
            ->hideOnForm();

        yield DateTimeField::new('lastLogin')
            ->setLabel('Date de dernière connexion')
            ->hideOnForm();

        yield TextField::new('accountStatus')
            ->setLabel('Compte actif')
            ->onlyOnDetail();
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        /** @var User $entityInstance */
        if ($entityInstance->getPlainPassword()) {
            $entityInstance->setPassword(
                $this->passwordHasher->hashPassword($entityInstance, $entityInstance->getPlainPassword())
            );
            $entityInstance->setPlainPassword(null);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        /** @var User $entityInstance */
        $this->userDeletionService->delete($entityInstance);
    }

    #[Route('/super-admin/user/{id}/toggle-account', name: 'super_admin_toggle_user_account')]
    public function toggleAccount(User $user): RedirectResponse
    {
        return $this->toggleAccountAction($user);
    }
}
