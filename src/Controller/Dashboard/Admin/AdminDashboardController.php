<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\User;
use App\Repository\ArticleRepository;
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
            ->setTitle('Super Admin Dashboard');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::subMenu('Mon espace personnelle')->setSubItems(
            [
                MenuItem::linkToDashboard('Mon compte', 'fas fa-user'),
                MenuItem::linkTo(AdminOwnArticleCrudController::class, "Mes articles", 'fas fa-book'),
            ]);
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fas fa-users');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fas fa-list');
        yield MenuItem::subMenu('Ressources', 'fas fa-folder')->setSubItems([
            MenuItem::linkTo(ArticleCrudController::class, 'Articles', 'fas fa-book'),
        ]);
        yield MenuItem::linkToLogout('Logout', 'fa fa-sign-out');
    }
}
