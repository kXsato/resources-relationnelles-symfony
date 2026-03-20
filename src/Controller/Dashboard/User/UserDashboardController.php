<?php

namespace App\Controller\Dashboard\User;

use App\Entity\User;
use App\Controller\Dashboard\User\UserCompletedProgressCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/mon-compte', routeName: 'user_dashboard')]
class UserDashboardController extends AbstractDashboardController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->redirect(
            $this->adminUrlGenerator
                ->setDashboard(self::class)
                ->setController(UserProfileCrudController::class)
                ->setAction('edit')
                ->setEntityId($user->getId())
                ->generateUrl()
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Mon compte');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Retour au site', 'fas fa-globe', 'https://resources-relationnelles.test/resources');
        yield MenuItem::linkToDashboard('Mon profil', 'fas fa-user');
        yield MenuItem::linkTo(UserArticleCrudController::class, 'Mes articles', 'fas fa-newspaper');
        yield MenuItem::linkTo(UserActivityCrudController::class, 'Mes activités', 'fas fa-running');
        yield MenuItem::linkTo(UserFavoriteCrudController::class, 'Mes favoris', 'fas fa-star');
        yield MenuItem::linkTo(UserProgressCrudController::class, 'Mes lectures en cours', 'fas fa-book-open');
        yield MenuItem::linkTo(UserCompletedProgressCrudController::class, 'Mes lectures terminées', 'fas fa-check-circle');
        yield MenuItem::linkToLogout('Déconnexion', 'fas fa-sign-out');
        
    }
}
