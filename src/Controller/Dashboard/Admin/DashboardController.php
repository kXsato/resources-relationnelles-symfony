<?php

namespace App\Controller\Dashboard\Admin;

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
    { 
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fas fa-users',);
        yield MenuItem::LinkTo(CategoryCrudController::class, 'Catégories', 'fas fa-list');
        yield MenuItem::subMenu ('Ressources','fas fa-folder')->setSubItems([
            yield MenuItem::linkTo(ArticleCrudController::class, 'Articles', 'fas fa-book')
        ]);
        yield MenuItem::linkToLogout('Logout', 'fa fa-sign-out');
       
    }
}
