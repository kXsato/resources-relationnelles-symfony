<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\Admin\AdminProgressCrudController;
use App\Controller\Dashboard\Admin\AdminCompletedProgressCrudController;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\Dashboard\Admin\AdminCommentCrudController;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class AdminDashboardController extends AbstractDashboardController
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
            ->setTitle('Espace Admin');
    }

    public function configureMenuItems(): iterable
    {
        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();
        $pendingCount = $this->articleRepository->countPendingExcludingAuthor($admin);
        $reactivationCount = $this->userRepository->countReactivationRequests();

        yield MenuItem::subMenu('Mon espace personnelle')->setSubItems([
            MenuItem::linkToCrud('Mon compte', 'fas fa-user', \App\Entity\User::class)->setController(AdminProfileCrudController::class)->setAction('edit')->setEntityId($admin->getId()),
            MenuItem::linkTo(AdminOwnArticleCrudController::class, 'Mes articles', 'fas fa-book'),
            MenuItem::linkTo(AdminFavoriteCrudController::class, 'Mes favoris', 'fas fa-star'),
            MenuItem::linkTo(AdminProgressCrudController::class, 'Mes lectures en cours', 'fas fa-book-open'),
            MenuItem::linkTo(AdminCompletedProgressCrudController::class, 'Mes lectures terminées', 'fas fa-check-circle'),
            MenuItem::linkTo(AdminCommentCrudController::class, 'Commentaires', 'fas fa-comments'),
        ]);

        yield MenuItem::linkTo(AdminUserCrudController::class, 'Utilisateurs', 'fas fa-users')
            ->setBadge($reactivationCount > 0 ? $reactivationCount : null, 'warning');
        yield MenuItem::linkTo(AdminCategoryCrudController::class, 'Catégories', 'fas fa-list');
        yield MenuItem::linkTo(AdminArticleCrudController::class, 'Articles en attente', 'fas fa-book')
            ->setBadge($pendingCount > 0 ? $pendingCount : null, 'danger');
        yield MenuItem::linkTo(AdminPublishedArticleCrudController::class, 'Articles publiés', 'fas fa-check');
        yield MenuItem::linkToDashboard('Statistiques', 'fas fa-chart-bar');

        yield MenuItem::linkToLogout('Logout', 'fa fa-sign-out');
    }
}