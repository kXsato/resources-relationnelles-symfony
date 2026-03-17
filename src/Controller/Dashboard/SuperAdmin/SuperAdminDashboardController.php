<?php

namespace App\Controller\Dashboard\SuperAdmin;

use App\Entity\User;
use App\Controller\Dashboard\SuperAdmin\SuperAdminProgressCrudController;
use App\Controller\Dashboard\SuperAdmin\SuperAdminCompletedProgressCrudController;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/super-admin', routeName: 'super_admin')]
class SuperAdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private UserRepository $userRepository,
    ) {}

    public function index(): Response
    {
        return $this->render('admin/common/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Espace Super Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('🌐 Voir les ressources', 'fas fa-globe', 'https://resources-relationnelles.test/resources');

        /** @var User $user */
        $user = $this->getUser();
        $pendingCount = $this->articleRepository->countPendingExcludingAuthor($user);
        $reactivationCount = $this->userRepository->countReactivationRequests();

        yield MenuItem::subMenu('Mon espace personnelle')->setSubItems([
            MenuItem::linkTo(SuperAdminProfileCrudController::class, 'Mon compte', 'fas fa-user')->setAction('edit')->setEntityId($user->getId()),
            MenuItem::linkTo(SuperAdminOwnArticleCrudController::class, 'Mes articles', 'fas fa-book'),
            MenuItem::linkTo(SuperAdminFavoriteCrudController::class, 'Mes favoris', 'fas fa-star'),
            MenuItem::linkTo(SuperAdminProgressCrudController::class, 'Mes lectures en cours', 'fas fa-book-open'),
            MenuItem::linkTo(SuperAdminCompletedProgressCrudController::class, 'Mes lectures terminées', 'fas fa-check-circle'),
        ]);

        yield MenuItem::subMenu('Gestion')->setSubItems([
            MenuItem::linkTo(SuperAdminUserCrudController::class, 'Utilisateurs', 'fas fa-users')
                ->setBadge($reactivationCount > 0 ? $reactivationCount : null, 'warning'),
            MenuItem::linkTo(SuperAdminCategoryCrudController::class, 'Catégories', 'fas fa-list'),
            MenuItem::linkTo(SuperAdminArticleCrudController::class, 'Articles en attente', 'fas fa-book')
                ->setBadge($pendingCount > 0 ? $pendingCount : null, 'danger'),
            MenuItem::linkTo(SuperAdminPublishedArticleCrudController::class, 'Articles publiés', 'fas fa-check'),
        ]);

        yield MenuItem::linkToDashboard('Statistiques', 'fas fa-chart-bar');

        yield MenuItem::linkToLogout('Logout', 'fa fa-sign-out');
    }
}