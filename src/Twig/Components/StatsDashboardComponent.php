<?php

namespace App\Twig\Components;

use App\Service\StatsProviderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class StatsDashboardComponent extends AbstractController
{
    use DefaultActionTrait;

    /**
     * Date de début de la période (format Y-m-d).
     * Writable : liée à un <input type="date"> dans le template.
     */
    #[LiveProp(writable: true)]
    public string $startDate = '';

    /**
     * Date de fin de la période (format Y-m-d).
     * Writable : liée à un <input type="date"> dans le template.
     */
    #[LiveProp(writable: true)]
    public string $endDate = '';

    public function __construct(
        private readonly StatsProviderService $statsProvider,
        private readonly ChartBuilderInterface $chartBuilder,
    ) {}

    /**
     * Initialise la période par défaut (30 derniers jours) au premier rendu.
     * Non appelé lors des re-rendus LiveComponent : les LiveProps conservent leur état.
     */
    public function mount(): void
    {
        $this->endDate   = (new \DateTimeImmutable())->format('Y-m-d');
        $this->startDate = (new \DateTimeImmutable('-30 days'))->format('Y-m-d');
    }

    // -----------------------------------------------------------------------
    // Helpers internes
    // -----------------------------------------------------------------------

    private function getFrom(): \DateTimeImmutable
    {
        return $this->startDate !== ''
            ? new \DateTimeImmutable($this->startDate)
            : new \DateTimeImmutable('-30 days');
    }

    private function getTo(): \DateTimeImmutable
    {
        return $this->endDate !== ''
            ? new \DateTimeImmutable($this->endDate)
            : new \DateTimeImmutable();
    }

    /**
     * Retourne le preset actif (7, 30 ou 90) si la période correspond exactement,
     * null sinon (plage personnalisée).
     */
    public function getActiveDays(): ?int
    {
        $today = (new \DateTimeImmutable())->format('Y-m-d');

        foreach ([7, 30, 90] as $days) {
            $expected = (new \DateTimeImmutable("-{$days} days"))->format('Y-m-d');
            if ($this->startDate === $expected && $this->endDate === $today) {
                return $days;
            }
        }

        return null;
    }

    // -----------------------------------------------------------------------
    // Données brutes (tableaux)
    // -----------------------------------------------------------------------

    public function getGlobalStats(): array
    {
        return $this->statsProvider->getGlobalStats();
    }

    public function getMostReadResources(): array
    {
        return $this->statsProvider->getMostReadResources(10);
    }

    public function getAverageReadingProgressPerResource(): array
    {
        return $this->statsProvider->getAverageReadingProgressPerResource();
    }

    // -----------------------------------------------------------------------
    // Graphiques Chart.js
    // -----------------------------------------------------------------------

    /**
     * Courbe : nouveaux inscrits par jour sur la période filtrée.
     */
    public function getNewUsersChart(): Chart
    {
        $rows = $this->statsProvider->getNewUsersPerDay($this->getFrom(), $this->getTo());

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels'   => array_column($rows, 'day'),
            'datasets' => [[
                'label'           => 'Nouveaux inscrits',
                'data'            => array_map('intval', array_column($rows, 'total')),
                'borderColor'     => 'rgb(79, 70, 229)',
                'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                'fill'            => true,
                'tension'         => 0.3,
                'pointRadius'     => 3,
            ]],
        ]);
        $chart->setOptions([
            'responsive' => true,
            'plugins'    => ['legend' => ['display' => false]],
            'scales'     => ['y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]]],
        ]);

        return $chart;
    }

    /**
     * Courbe : ressources publiées par jour sur la période filtrée.
     */
    public function getPublishedResourcesChart(): Chart
    {
        $rows = $this->statsProvider->getPublishedResourcesPerDay($this->getFrom(), $this->getTo());

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels'   => array_column($rows, 'day'),
            'datasets' => [[
                'label'           => 'Ressources publiées',
                'data'            => array_map('intval', array_column($rows, 'total')),
                'borderColor'     => 'rgb(5, 150, 105)',
                'backgroundColor' => 'rgba(5, 150, 105, 0.1)',
                'fill'            => true,
                'tension'         => 0.3,
                'pointRadius'     => 3,
            ]],
        ]);
        $chart->setOptions([
            'responsive' => true,
            'plugins'    => ['legend' => ['display' => false]],
            'scales'     => ['y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]]],
        ]);

        return $chart;
    }

    /**
     * Doughnut : répartition des ressources publiées par catégorie.
     */
    public function getResourcesByCategoryChart(): Chart
    {
        $rows = $this->statsProvider->getResourcesByCategoryStats();

        $palette = [
            'rgba(79, 70, 229, 0.8)',
            'rgba(5, 150, 105, 0.8)',
            'rgba(217, 119, 6, 0.8)',
            'rgba(220, 38, 38, 0.8)',
            'rgba(124, 58, 237, 0.8)',
            'rgba(14, 165, 233, 0.8)',
            'rgba(236, 72, 153, 0.8)',
            'rgba(16, 185, 129, 0.8)',
        ];

        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels'   => array_column($rows, 'categoryName'),
            'datasets' => [[
                'data'            => array_map('intval', array_column($rows, 'total')),
                'backgroundColor' => array_slice($palette, 0, count($rows)),
                'borderWidth'     => 2,
                'borderColor'     => '#fff',
            ]],
        ]);
        $chart->setOptions([
            'responsive' => true,
            'plugins'    => [
                'legend' => ['position' => 'right'],
            ],
        ]);

        return $chart;
    }

    /**
     * Barres horizontales : top articles les plus mis en favoris.
     */
    public function getTopFavoritedArticlesChart(): Chart
    {
        $rows = $this->statsProvider->getTopFavoritedArticles(8);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels'   => array_column($rows, 'title'),
            'datasets' => [[
                'label'           => 'Favoris',
                'data'            => array_map('intval', array_column($rows, 'total')),
                'backgroundColor' => 'rgba(217, 119, 6, 0.75)',
                'borderColor'     => 'rgb(217, 119, 6)',
                'borderWidth'     => 1,
                'borderRadius'    => 4,
            ]],
        ]);
        $chart->setOptions([
            'indexAxis' => 'y',
            'responsive' => true,
            'plugins'    => ['legend' => ['display' => false]],
            'scales'     => ['x' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]]],
        ]);

        return $chart;
    }

    // -----------------------------------------------------------------------
    // Actions LiveComponent
    // -----------------------------------------------------------------------

    /**
     * Applique un preset rapide (7, 30 ou 90 jours) en mettant à jour
     * startDate et endDate simultanément.
     */
    #[LiveAction]
    public function setPeriod(int $days): void
    {
        $this->endDate   = (new \DateTimeImmutable())->format('Y-m-d');
        $this->startDate = (new \DateTimeImmutable("-{$days} days"))->format('Y-m-d');
    }
}
