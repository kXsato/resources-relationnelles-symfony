<?php

namespace App\Repository;

use App\Entity\Favorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * Articles les plus mis en favoris, triés par popularité.
     * Retourne [['articleId' => N, 'title' => '...', 'total' => N], ...]
     */
    public function countFavoritesPerArticle(int $limit = 10): array
    {
        return $this->createQueryBuilder('f')
            ->select('IDENTITY(f.article) AS articleId, a.title, COUNT(f.id) AS total')
            ->join('f.article', 'a')
            ->groupBy('f.article')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Nombre total de favoris.
     */
    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
