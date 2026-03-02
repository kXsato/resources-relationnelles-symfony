<?php

namespace App\Controller\Admin;

use App\Entity\Category;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin_user_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Super Admin Dashboard');
    }

    public function configureMenuItems(): iterable
    { return [
        MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fas fa-users',),
        MenuItem::LinkTo(CategoryCrudController::class, 'Catégories', 'fas fa-list'),
        MenuItem::subMenu('Ressources', 'fas fa-folder')->setSubItems([
            MenuItem::linkTo(ArticleCrudController::class, 'Articles', 'fas fa-book'),
        ]),
        MenuItem::linkToLogout('Logout', 'fa fa-sign-out'),
        ];

    }
}
