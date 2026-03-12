<?php

namespace App\Repository;

use App\Entity\UserRessourceProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRessourceProgress>
 */
class UserRessourceProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRessourceProgress::class);
    }

    /**
     * Retourne les entrées complétées dont la date de complétion est antérieure au seuil donné.
     *
     * @return UserRessourceProgress[]
     */
    public function findCompletedBefore(\DateTimeInterface $threshold): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.completeAt < :threshold')
            ->setParameter('status', 'completed')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les entrées orphelines : sans utilisateur ou sans ressource.
     *
     * @return UserRessourceProgress[]
     */
    public function findOrphaned(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.UserRessources IS NULL')
            ->orWhere('p.resource IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Pourcentage de lecture moyen par ressource.
     * Retourne [['resourceId' => N, 'title' => '...', 'avgPercentage' => N], ...]
     */
    public function averageReadPercentagePerResource(): array
    {
        return $this->createQueryBuilder('p')
            ->select('IDENTITY(p.resource) AS resourceId, r.title, AVG(p.readPercentage) AS avgPercentage')
            ->join('p.resource', 'r')
            ->groupBy('p.resource')
            ->orderBy('avgPercentage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Répartition des progressions par statut (in_progress / completed).
     * Retourne [['status' => '...', 'total' => N], ...]
     */
    public function countByStatus(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) AS total')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();
    }

    /**
     * Ressources les plus lues, triées par nombre de lecteurs uniques.
     * Retourne [['resourceId' => N, 'title' => '...', 'readers' => N], ...]
     */
    public function findMostReadResources(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->select('IDENTITY(p.resource) AS resourceId, r.title, COUNT(DISTINCT p.UserRessources) AS readers')
            ->join('p.resource', 'r')
            ->groupBy('p.resource')
            ->orderBy('readers', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
