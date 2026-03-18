<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Resource;
use App\Service\AgeVerificationService;
use Doctrine\ORM\QueryBuilder;

class ResourceAdultExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private readonly AgeVerificationService $ageVerification) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (!is_a($resourceClass, Resource::class, true)) {
            return;
        }

        if ($this->ageVerification->isCurrentUserAdult()) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.isAdultOnly = :isAdultOnly', $rootAlias))
            ->setParameter('isAdultOnly', false);
    }
}
