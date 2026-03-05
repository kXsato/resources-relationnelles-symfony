<?php

namespace App\Controller\Dashboard\Moderator;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tableau de bord des modérateurs.
 * Accessible uniquement aux utilisateurs ayant le rôle ROLE_MODERATOR.
 */
#[AdminDashboard(routePath: '/moderator', routeName: 'moderator_dashboard')]
class ModeratorDashboardController extends AbstractDashboardController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->redirect(
            $this->adminUrlGenerator
                ->setDashboard(self::class)
                ->setController(ModeratorProfileCrudController::class)
                ->setAction('edit')
                ->setEntityId($user->getId())
                ->generateUrl()
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Espace modérateur');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Mon profil', 'fas fa-user');
        yield MenuItem::linkToLogout('Déconnexion', 'fas fa-sign-out');
    }
}
