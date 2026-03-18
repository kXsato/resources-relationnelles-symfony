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
    public function findPublished(?int $categoryId = null, ?string $type = null, ?int $limit = null, bool $showAdult = false): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('r.id', 'DESC');

        if (!$showAdult) {
            $qb->andWhere('r.isAdultOnly = :isAdultOnly')
               ->setParameter('isAdultOnly', false);
        }

        if ($categoryId !== null) {
            $qb->join('r.categories', 'c')
               ->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        if ($type === 'article') {
            $qb->andWhere('r INSTANCE OF App\Entity\Article');
        } elseif ($type === 'activity') {
            $qb->andWhere('r INSTANCE OF App\Entity\Activity');
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Nombre de ressources par statut.
     * Retourne [['status' => '...', 'total' => N], ...]
     */
    /**
     * @return Resource[]
     */
    public function findRelated(int $excludeId, int $limit = 3): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.id != :id')
            ->setParameter('status', 'published')
            ->setParameter('id', $excludeId)
            ->orderBy('r.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

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
     * Nombre de ressources créées par jour (tous statuts) sur une période donnée.
     * Retourne [['day' => 'YYYY-MM-DD', 'total' => N], ...]
     */
    public function countCreatedPerDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql  = 'SELECT DATE(created_at) AS day, COUNT(id) AS total
                 FROM resource
                 WHERE created_at BETWEEN :from AND :to
                 GROUP BY DATE(created_at)
                 ORDER BY day ASC';

        return $conn->executeQuery($sql, [
            'from' => $from->format('Y-m-d 00:00:00'),
            'to'   => $to->format('Y-m-d 23:59:59'),
        ])->fetchAllAssociative();
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
     * Nombre de ressources créées par catégorie (tous statuts).
     * Retourne [['categoryName' => '...', 'total' => N], ...]
     */
    public function countAllByCategory(): array
    {
        return $this->createQueryBuilder('r')
            ->select('c.name AS categoryName, COUNT(r.id) AS total')
            ->join('r.categories', 'c')
            ->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Popularité des ressources publiées : vues (lecteurs uniques) + favoris.
     * Retourne [['title' => '...', 'views' => N, 'favorites' => N], ...]
     */
    public function getPopularityStats(int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql  = 'SELECT r.title,
                        COUNT(DISTINCT p.user_ressources_id) AS views,
                        COUNT(DISTINCT f.id)                 AS favorites
                 FROM resource r
                 LEFT JOIN user_ressource_progress p ON p.resource_id = r.id
                 LEFT JOIN favorite f               ON f.article_id   = r.id
                 WHERE r.status = :status
                 GROUP BY r.id, r.title
                 ORDER BY views DESC, favorites DESC
                 LIMIT :lim';

        return $conn->executeQuery($sql, [
            'status' => 'published',
            'lim'    => $limit,
        ], [
            'lim' => \Doctrine\DBAL\ParameterType::INTEGER,
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
