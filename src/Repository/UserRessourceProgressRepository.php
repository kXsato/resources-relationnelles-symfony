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
}
