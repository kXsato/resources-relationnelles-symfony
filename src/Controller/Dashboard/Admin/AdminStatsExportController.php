<?php

namespace App\Controller\Dashboard\Admin;

use App\Service\StatsProviderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/admin/stats/export', name: 'admin_stats_export')]
#[IsGranted('ROLE_ADMIN')]
class AdminStatsExportController extends AbstractController
{
    public function __construct(
        private readonly StatsProviderService $statsProvider,
        private readonly SerializerInterface $serializer,
    ) {}

    public function __invoke(Request $request): Response
    {
        $type      = $request->query->getString('type', 'users');
        $startDate = $request->query->getString('startDate', (new \DateTimeImmutable('-30 days'))->format('Y-m-d'));
        $endDate   = $request->query->getString('endDate',   (new \DateTimeImmutable())->format('Y-m-d'));

        [$data, $filename] = match ($type) {
            'users'                => $this->exportUsers(),
            'resources_per_day'    => $this->exportResourcesPerDay($startDate, $endDate),
            'resources_by_category'=> $this->exportResourcesByCategory(),
            'popularity'           => $this->exportPopularity(),
            default                => throw new BadRequestHttpException("Type d'export inconnu : {$type}"),
        };

        $csv = $this->serializer->serialize($data, 'csv', [
            CsvEncoder::DELIMITER_KEY => ';',
            CsvEncoder::NO_HEADERS_KEY => false,
        ]);

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // -------------------------------------------------------------------------

    private function exportUsers(): array
    {
        $stats = $this->statsProvider->getGlobalStats();

        $data = [[
            'Utilisateurs totaux'           => $stats['users']['total'],
            'Utilisateurs actifs'           => $stats['users']['active'],
            'Utilisateurs inactifs'         => $stats['users']['inactive'],
            'Demandes de réactivation'      => $stats['users']['reactivationRequests'],
            'Total ressources'              => $stats['resources']['total'],
            'Ressources publiées'           => $stats['resources']['byStatus']['published'] ?? 0,
            'Ressources rejetées'           => $stats['resources']['byStatus']['rejected'] ?? 0,
            'Ressources en attente'         => $stats['resources']['byStatus']['pending'] ?? 0,
            'Lectures totales'              => $stats['progress']['total'],
            'Favoris totaux'                => $stats['favorites']['total'],
            'Utilisateurs actifs (%)'       => $this->statsProvider->getActiveUsersPercentage(),
            'Taux de rétention (%)'         => $this->statsProvider->getRetentionRate(),
            'Moy. ressources / actif'       => $this->statsProvider->getAvgResourcesPerActiveUser(),
        ]];

        return [$data, 'stats_utilisateurs_' . date('Y-m-d') . '.csv'];
    }

    private function exportResourcesPerDay(string $startDate, string $endDate): array
    {
        $from = new \DateTimeImmutable($startDate);
        $to   = new \DateTimeImmutable($endDate);
        $rows = $this->statsProvider->getResourcesCreatedPerDay($from, $to);

        $data = array_map(fn(array $row) => [
            'Jour'  => $row['day'],
            'Total' => (int) $row['total'],
        ], $rows);

        $filename = "stats_ressources_{$startDate}_{$endDate}.csv";

        return [$data, $filename];
    }

    private function exportResourcesByCategory(): array
    {
        $rows = $this->statsProvider->getResourcesByCategory();

        $data = array_map(fn(array $row) => [
            'Catégorie' => $row['categoryName'],
            'Total'     => (int) $row['total'],
        ], $rows);

        return [$data, 'stats_categories_' . date('Y-m-d') . '.csv'];
    }

    private function exportPopularity(): array
    {
        $rows = $this->statsProvider->getResourcePopularity(50);

        $data = array_map(fn(array $row) => [
            'Ressource' => $row['title'],
            'Vues'      => (int) $row['views'],
            'Favoris'   => (int) $row['favorites'],
        ], $rows);

        return [$data, 'stats_popularite_' . date('Y-m-d') . '.csv'];
    }
}
