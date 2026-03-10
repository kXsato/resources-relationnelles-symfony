<?php

namespace App\Controller\Dashboard\Common;

use App\Contract\OwnProfileCrudControllerInterface;
use App\Entity\User;
use App\Service\UserDeletionService;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Classe de base abstraite pour la gestion du profil personnel d'un utilisateur connecté.
 *
 * Chaque dashboard hérite de cette classe. La logique est centralisée ici :
 * filtrage par utilisateur connecté, champs affichés, actions disponibles.
 */
abstract class BaseOwnProfileCrudController extends AbstractCrudController implements OwnProfileCrudControllerInterface
{
    public function __construct(
        protected Security $security,
        protected UserDeletionService $userDeletionService,
        protected TokenStorageInterface $tokenStorage,
    ) {}

    abstract protected function getDeleteAccountRouteName(): string;

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPageTitle('edit', 'Mon profil');
    }

    public function configureActions(Actions $actions): Actions
    {
        $deleteAccount = Action::new('deleteAccount', 'Supprimer mon compte')
            ->setIcon('fas fa-user-slash')
            ->linkToRoute($this->getDeleteAccountRouteName())
            ->setTemplatePath('admin/user/delete_own_account_action.html.twig');

        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::INDEX, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_EDIT, $deleteAccount);
    }

    /**
     * Restreint la requête à l'utilisateur connecté uniquement.
     */
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        /** @var User $user */
        $user = $this->security->getUser();
        $qb->andWhere('entity.id = :userId')
           ->setParameter('userId', $user->getId());

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('username', "Nom d'utilisateur");
        yield EmailField::new('email', 'Adresse e-mail');
        yield DateTimeField::new('birthDate', 'Date de naissance');
    }

    protected function deleteOwnAccountAction(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_own_account', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        /** @var User $user */
        $user = $this->security->getUser();
        $this->userDeletionService->delete($user);

        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('success', 'Votre compte a été supprimé définitivement.');

        return $this->redirectToRoute('app_login');
    }
}
