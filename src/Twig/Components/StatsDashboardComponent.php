<?php

namespace App\Twig\Components;

use App\Service\StatsProviderService;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class StatsDashboardComponent
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

    // -----------------------------------------------------------------------
    // Graphiques Chart.js
    // -----------------------------------------------------------------------

    /**
     * Courbe : ressources créées par jour (tous statuts) sur la période filtrée.
     */
    public function getResourcesCreatedChart(): Chart
    {
        $rows  = $this->statsProvider->getResourcesCreatedPerDay($this->getFrom(), $this->getTo());
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels'   => array_column($rows, 'day'),
            'datasets' => [[
                'label'           => 'Ressources créées',
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
     * Doughnut : ressources validées (published) vs rejetées (rejected).
     */
    public function getValidatedVsRejectedChart(): Chart
    {
        $stats = $this->statsProvider->getValidatedVsRejectedStats();
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels'   => ['Validées', 'Rejetées'],
            'datasets' => [[
                'data'            => [$stats['published'], $stats['rejected']],
                'backgroundColor' => ['rgba(5, 150, 105, 0.8)', 'rgba(220, 38, 38, 0.8)'],
                'borderColor'     => ['rgb(5, 150, 105)', 'rgb(220, 38, 38)'],
                'borderWidth'     => 2,
            ]],
        ]);
        $chart->setOptions([
            'responsive' => true,
            'plugins'    => ['legend' => ['position' => 'bottom']],
        ]);

        return $chart;
    }

    /**
     * Doughnut : % d'utilisateurs actifs vs inactifs.
     */
    public function getActiveUsersChart(): Chart
    {
        $percentage = $this->statsProvider->getActiveUsersPercentage();
        $inactive   = round(100 - $percentage, 1);
        $chart      = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels'   => ['Actifs', 'Inactifs'],
            'datasets' => [[
                'data'            => [$percentage, $inactive],
                'backgroundColor' => ['rgba(79, 70, 229, 0.8)', 'rgba(209, 213, 219, 0.6)'],
                'borderColor'     => ['rgb(79, 70, 229)', 'rgb(209, 213, 219)'],
                'borderWidth'     => 2,
            ]],
        ]);
        $chart->setOptions([
            'responsive' => true,
            'plugins'    => ['legend' => ['position' => 'bottom']],
        ]);

        return $chart;
    }

    public function getActiveUsersPercentage(): float
    {
        return $this->statsProvider->getActiveUsersPercentage();
    }

    public function getAvgResourcesPerActiveUser(): float
    {
        return $this->statsProvider->getAvgResourcesPerActiveUser();
    }

    public function getRetentionRate(): float
    {
        return $this->statsProvider->getRetentionRate();
    }

    public function getResourcePopularity(): array
    {
        return $this->statsProvider->getResourcePopularity(10);
    }

    /**
     * Barres horizontales : volume de ressources créées par catégorie (tous statuts).
     */
    public function getResourcesByCategoryChart(): Chart
    {
        $rows   = $this->statsProvider->getResourcesByCategory();
        $chart  = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels'   => array_column($rows, 'categoryName'),
            'datasets' => [[
                'label'           => 'Ressources',
                'data'            => array_map('intval', array_column($rows, 'total')),
                'backgroundColor' => 'rgba(14, 165, 233, 0.75)',
                'borderColor'     => 'rgb(14, 165, 233)',
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
    public function setPeriod(#[LiveArg] int $days): void
    {
        $this->endDate   = (new \DateTimeImmutable())->format('Y-m-d');
        $this->startDate = (new \DateTimeImmutable("-{$days} days"))->format('Y-m-d');
    }
}
