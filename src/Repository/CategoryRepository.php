<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Catégories avec leur nombre de ressources publiées associées.
     * Retourne [['id' => N, 'name' => '...', 'total' => N], ...]
     */
    public function findWithPublishedResourceCount(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id, c.name, COUNT(r.id) AS total')
            ->leftJoin('c.resources', 'r', 'WITH', 'r.status = :status')
            ->setParameter('status', 'published')
            ->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
