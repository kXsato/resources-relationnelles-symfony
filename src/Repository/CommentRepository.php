<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findRootComments(Resource $resource): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.resource = :resource')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isPublished = true')
            ->setParameter('resource', $resource)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}