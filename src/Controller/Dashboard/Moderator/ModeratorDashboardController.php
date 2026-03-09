<?php

namespace App\Controller\Dashboard\Moderator;

use App\Entity\User;
use App\Enum\ResourceStatus;
use App\Repository\ArticleRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
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
                ->setController(ModeratorProfileCrudController::class)
                ->setAction('edit')
                ->setEntityId($user->getId())
                ->generateUrl()
        );
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addAssetMapperEntry('app');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Espace modérateur');
    }

    public function configureMenuItems(): iterable
    {
        /** @var \App\Entity\User $moderator */
        $moderator = $this->getUser();
        $pendingCount = $this->articleRepository->countPendingExcludingAuthor($moderator);

        yield MenuItem::linkToDashboard('Mon profil', 'fas fa-user');

        yield MenuItem::subMenu('Mes ressources')->setSubItems([
            MenuItem::linkTo(ModeratorOwnArticleCrudController::class, 'Mes articles', 'fas fa-newspaper'),
            MenuItem::linkTo(ModeratorFavoriteCrudController::class, 'Mes favoris', 'fas fa-star'),
            ]);
        yield MenuItem::subMenu('Modération')->setSubItems([
            MenuItem::linkTo(ModeratorArticleCrudController::class, 'Articles en attente', 'fas fa-clock')
                ->setBadge($pendingCount > 0 ? $pendingCount : null, 'danger'),
            MenuItem::linkTo(ModeratorPublishedArticleCrudController::class, 'Articles publiés', 'fas fa-check'),
        ]);
        yield MenuItem::linkToLogout('Déconnexion', 'fas fa-sign-out');
    }
}
