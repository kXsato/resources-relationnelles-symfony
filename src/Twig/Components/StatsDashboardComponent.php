<?php

namespace App\Twig\Components;

use App\Service\StatsProviderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class StatsDashboardComponent extends AbstractController
{
    use DefaultActionTrait;

    /**
     * Période d'analyse en jours (modifiable par l'utilisateur sans rechargement de page).
     */
    #[LiveProp(writable: true)]
    public int $days = 30;

    public function __construct(
        private readonly StatsProviderService $statsProvider,
    ) {}

    // -----------------------------------------------------------------------
    // Données calculées à chaque re-rendu du composant
    // -----------------------------------------------------------------------

    public function getGlobalStats(): array
    {
        return $this->statsProvider->getGlobalStats();
    }

    public function getNewUsersPerDay(): array
    {
        $from = new \DateTimeImmutable("-{$this->days} days");
        $to   = new \DateTimeImmutable();

        return $this->statsProvider->getNewUsersPerDay($from, $to);
    }

    public function getPublishedResourcesPerDay(): array
    {
        $from = new \DateTimeImmutable("-{$this->days} days");
        $to   = new \DateTimeImmutable();

        return $this->statsProvider->getPublishedResourcesPerDay($from, $to);
    }

    public function getMostReadResources(): array
    {
        return $this->statsProvider->getMostReadResources(10);
    }

    public function getTopFavoritedArticles(): array
    {
        return $this->statsProvider->getTopFavoritedArticles(10);
    }

    public function getResourcesByCategoryStats(): array
    {
        return $this->statsProvider->getResourcesByCategoryStats();
    }

    public function getAverageReadingProgressPerResource(): array
    {
        return $this->statsProvider->getAverageReadingProgressPerResource();
    }

    // -----------------------------------------------------------------------
    // Actions LiveComponent
    // -----------------------------------------------------------------------

    #[LiveAction]
    public function setPeriod(int $days): void
    {
        $this->days = $days;
    }
}
