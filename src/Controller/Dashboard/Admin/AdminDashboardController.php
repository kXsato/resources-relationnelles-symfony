<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class AdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private ArticleRepository $articleRepository,
        private UserRepository $userRepository,
    ) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->redirect(
            $this->adminUrlGenerator
                ->setDashboard(self::class)
                ->setController(AdminProfileCrudController::class)
                ->setAction('edit')
                ->setEntityId($user->getId())
                ->generateUrl()
        );
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

        yield MenuItem::subMenu('Mon espace personnelle')->setSubItems(
            [
                MenuItem::linkToDashboard('Mon compte', 'fas fa-user'),
                MenuItem::linkTo(AdminOwnArticleCrudController::class, "Mes articles", 'fas fa-book'),
            ]);

        yield MenuItem::subMenu('Gestion')->setSubItems(
            [
                MenuItem::linkTo(AdminUserCrudController::class, 'Utilisateurs', 'fas fa-users')
                    ->setBadge($reactivationCount > 0 ? $reactivationCount : null, 'warning'),
                MenuItem::linkTo(AdminCategoryCrudController::class, 'Catégories', 'fas fa-list'),
                MenuItem::linkTo(AdminArticleCrudController::class, 'Articles en attente', 'fas fa-book')
                    ->setBadge($pendingCount > 0 ? $pendingCount : null, 'danger'),
                MenuItem::linkTo(AdminPublishedArticleCrudController::class, 'Articles publiés', 'fas fa-check'),
            ]);
        
        yield MenuItem::linkToLogout('Logout', 'fa fa-sign-out');
    }
}
