<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\FavoriteRepository;
use App\Repository\ResourceRepository;
use App\Repository\UserRepository;
use App\Repository\UserRessourceProgressRepository;

/**
 * Centralise tous les calculs statistiques de l'application.
 *
 * Injecter ce service dans les contrôleurs à la place des repositories
 * individuels, afin de garder les contrôleurs légers et les calculs
 * testables en un seul endroit.
 */
class StatsProviderService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ResourceRepository $resourceRepository,
        private readonly UserRessourceProgressRepository $progressRepository,
        private readonly FavoriteRepository $favoriteRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ArticleRepository $articleRepository,
    ) {}

    /**
     * Vue d'ensemble globale pour les tableaux de bord admin/superadmin.
     *
     * Retourne :
     * [
     *   'users'     => ['active' => N, 'inactive' => N, 'total' => N, 'reactivationRequests' => N],
     *   'resources' => ['byStatus' => ['published' => N, ...], 'total' => N],
     *   'progress'  => ['byStatus' => ['in_progress' => N, 'completed' => N], 'total' => N],
     *   'favorites' => ['total' => N],
     * ]
     */
    public function getGlobalStats(): array
    {
        $userStatus = $this->userRepository->countByActivationStatus();

        $resourceByStatus = [];
        foreach ($this->resourceRepository->countByStatus() as $row) {
            $resourceByStatus[$row['status']] = (int) $row['total'];
        }

        $progressByStatus = [];
        foreach ($this->progressRepository->countByStatus() as $row) {
            $progressByStatus[$row['status']] = (int) $row['total'];
        }

        return [
            'users' => [
                'active'               => $userStatus['active'],
                'inactive'             => $userStatus['inactive'],
                'total'                => $userStatus['active'] + $userStatus['inactive'],
                'reactivationRequests' => $this->userRepository->countReactivationRequests(),
            ],
            'resources' => [
                'byStatus' => $resourceByStatus,
                'total'    => array_sum($resourceByStatus),
            ],
            'progress' => [
                'byStatus' => $progressByStatus,
                'total'    => array_sum($progressByStatus),
            ],
            'favorites' => [
                'total' => $this->favoriteRepository->countTotal(),
            ],
        ];
    }

    /**
     * Nombre d'articles en attente de validation, hors articles de l'auteur donné.
     * Utilisé pour les badges de menu des dashboards.
     */
    public function getPendingArticlesCount(User $author): int
    {
        return $this->articleRepository->countPendingExcludingAuthor($author);
    }

    /**
     * Nombre de demandes de réactivation en attente.
     * Utilisé pour les badges de menu des dashboards.
     */
    public function getReactivationRequestsCount(): int
    {
        return $this->userRepository->countReactivationRequests();
    }

    /**
     * Nouveaux inscrits par jour sur une période donnée.
     * Retourne [['day' => 'YYYY-MM-DD', 'total' => N], ...]
     */
    public function getNewUsersPerDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->userRepository->countNewUsersPerDay($from, $to);
    }

    /**
     * Ressources publiées créées par jour sur une période donnée.
     * Retourne [['day' => 'YYYY-MM-DD', 'total' => N], ...]
     */
    public function getPublishedResourcesPerDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->resourceRepository->countPublishedPerDay($from, $to);
    }

    /**
     * Répartition des ressources publiées par catégorie.
     * Retourne [['categoryName' => '...', 'total' => N], ...]
     */
    public function getResourcesByCategoryStats(): array
    {
        return $this->resourceRepository->countPublishedByCategory();
    }

    /**
     * Pourcentage de lecture moyen par ressource, du plus lu au moins lu.
     * Retourne [['resourceId' => N, 'title' => '...', 'avgPercentage' => N], ...]
     */
    public function getAverageReadingProgressPerResource(): array
    {
        return $this->progressRepository->averageReadPercentagePerResource();
    }

    /**
     * Ressources les plus lues, classées par nombre de lecteurs uniques.
     * Retourne [['resourceId' => N, 'title' => '...', 'readers' => N], ...]
     */
    public function getMostReadResources(int $limit = 10): array
    {
        return $this->progressRepository->findMostReadResources($limit);
    }

    /**
     * Articles les plus mis en favoris, classés par popularité.
     * Retourne [['articleId' => N, 'title' => '...', 'total' => N], ...]
     */
    public function getTopFavoritedArticles(int $limit = 10): array
    {
        return $this->favoriteRepository->countFavoritesPerArticle($limit);
    }

    /**
     * Toutes les catégories avec leur nombre de ressources publiées associées.
     * Retourne [['id' => N, 'name' => '...', 'total' => N], ...]
     */
    public function getCategoriesWithResourceCount(): array
    {
        return $this->categoryRepository->findWithPublishedResourceCount();
    }
}
