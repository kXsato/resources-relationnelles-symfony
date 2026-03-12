<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne les utilisateurs actifs inactifs depuis plus de $months mois.
     * Référence d'activité : lastLogin si disponible, sinon registrationDate.
     *
     * @return User[]
     */
    public function countReactivationRequests(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.reactivationRequestedAt IS NOT NULL')
            ->andWhere('u.isAccountActivated = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findInactiveActiveUsers(\DateTimeImmutable $threshold): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isAccountActivated = true')
            ->andWhere(
                '(u.lastLogin IS NOT NULL AND u.lastLogin < :threshold)
                OR (u.lastLogin IS NULL AND u.registrationDate < :threshold)'
            )
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * Nombre de nouveaux utilisateurs par jour sur une période donnée.
     * Retourne [['day' => 'YYYY-MM-DD', 'total' => N], ...]
     */
    public function countNewUsersPerDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT DATE(registration_date) AS day, COUNT(id) AS total
                FROM `user`
                WHERE registration_date BETWEEN :from AND :to
                GROUP BY DATE(registration_date)
                ORDER BY day ASC';

        return $conn->executeQuery($sql, [
            'from' => $from->format('Y-m-d 00:00:00'),
            'to'   => $to->format('Y-m-d 23:59:59'),
        ])->fetchAllAssociative();
    }

    /**
     * Nombre d'utilisateurs "retenus" : ceux dont le dernier login
     * est au moins 1 jour après la date d'inscription.
     */
    public function countRetainedUsers(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql  = 'SELECT COUNT(id) FROM `user`
                 WHERE last_login IS NOT NULL
                   AND DATEDIFF(last_login, registration_date) >= 1';

        return (int) $conn->executeQuery($sql)->fetchOne();
    }

    /**
     * Répartition des utilisateurs par statut d'activation.
     * Retourne ['active' => N, 'inactive' => N]
     */
    public function countByActivationStatus(): array
    {
        $rows = $this->createQueryBuilder('u')
            ->select('u.isAccountActivated AS activated, COUNT(u.id) AS total')
            ->groupBy('u.isAccountActivated')
            ->getQuery()
            ->getResult();

        $counts = ['active' => 0, 'inactive' => 0];
        foreach ($rows as $row) {
            $key = $row['activated'] ? 'active' : 'inactive';
            $counts[$key] = (int) $row['total'];
        }

        return $counts;
    }
}
