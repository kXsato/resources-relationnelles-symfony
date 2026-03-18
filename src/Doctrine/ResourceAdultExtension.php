<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Resource;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ResourceAdultExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {}

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

        if ($this->isCurrentUserAdult()) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.isAdultOnly = :isAdultOnly', $rootAlias))
            ->setParameter('isAdultOnly', false);
    }

    private function isCurrentUserAdult(): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $birthDate = $user->getBirthDate();
        if ($birthDate === null) {
            return false;
        }

        return $birthDate->diff(new \DateTimeImmutable())->y >= 18;
    }
}
