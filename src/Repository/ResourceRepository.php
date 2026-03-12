<?php

namespace App\Repository;

use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<resource>
 */
class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    /**
     * @return Resource[]
     */
    public function findPublished(?int $categoryId = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('r.id', 'DESC');

        if ($categoryId !== null) {
            $qb->join('r.categories', 'c')
               ->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Nombre de ressources par statut.
     * Retourne [['status' => '...', 'total' => N], ...]
     */
    public function countByStatus(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) AS total')
            ->groupBy('r.status')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Nombre de ressources publiées créées par jour sur une période donnée.
     * Retourne [['day' => 'YYYY-MM-DD', 'total' => N], ...]
     */
    public function countPublishedPerDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT DATE(created_at) AS day, COUNT(id) AS total
                FROM resource
                WHERE status = :status
                  AND created_at BETWEEN :from AND :to
                GROUP BY DATE(created_at)
                ORDER BY day ASC';

        return $conn->executeQuery($sql, [
            'status' => 'published',
            'from'   => $from->format('Y-m-d 00:00:00'),
            'to'     => $to->format('Y-m-d 23:59:59'),
        ])->fetchAllAssociative();
    }

    /**
     * Nombre de ressources publiées par catégorie.
     * Retourne [['categoryName' => '...', 'total' => N], ...]
     */
    public function countPublishedByCategory(): array
    {
        return $this->createQueryBuilder('r')
            ->select('c.name AS categoryName, COUNT(r.id) AS total')
            ->join('r.categories', 'c')
            ->where('r.status = :status')
            ->setParameter('status', 'published')
            ->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
