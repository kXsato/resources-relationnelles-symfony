<?php

namespace App\Controller\Dashboard\SuperAdmin;

use App\Entity\User;
use App\Repository\ArticleRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/super-admin', routeName: 'super_admin')]
class SuperAdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private ArticleRepository $articleRepository,
    ) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->redirect(
            $this->adminUrlGenerator
                ->setDashboard(self::class)
                ->setController(SuperAdminProfileCrudController::class)
                ->setAction('edit')
                ->setEntityId($user->getId())
                ->generateUrl()
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Espace Super Admin');
    }

    public function configureMenuItems(): iterable
    {
        /** @var User $user */
        $user = $this->getUser();
        $pendingCount = $this->articleRepository->countPendingExcludingAuthor($user);

        yield MenuItem::subMenu('Mon espace personnelle')->setSubItems([
            MenuItem::linkToDashboard('Mon compte', 'fas fa-user'),
            MenuItem::linkTo(SuperAdminOwnArticleCrudController::class, 'Mes articles', 'fas fa-book'),
        ]);

        yield MenuItem::subMenu('Gestion')->setSubItems([
            MenuItem::linkTo(SuperAdminUserCrudController::class, 'Utilisateurs', 'fas fa-users'),
            MenuItem::linkTo(SuperAdminCategoryCrudController::class, 'Catégories', 'fas fa-list'),
            MenuItem::linkTo(SuperAdminArticleCrudController::class, 'Articles en attente', 'fas fa-book')
                ->setBadge($pendingCount > 0 ? $pendingCount : null, 'danger'),
            MenuItem::linkTo(SuperAdminPublishedArticleCrudController::class, 'Articles publiés', 'fas fa-check'),
        ]);

        yield MenuItem::linkToLogout('Logout', 'fa fa-sign-out');
    }
}
