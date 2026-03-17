<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\UserRessourceProgress;
use App\Repository\ResourceRepository;
use App\Repository\UserRessourceProgressRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserRessourceProgressProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private UserRessourceProgressRepository $progressRepository,
        private ResourceRepository $resourceRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof UserRessourceProgress) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();

        // POST : initialise l'utilisateur, résout la resource via resourceId
        if ($data->getUserRessources() === null) {
            $resourceId = $data->getResourceId();

            if (!$resourceId) {
                throw new BadRequestHttpException('Le champ resourceId est requis.');
            }

            $resource = $this->resourceRepository->find($resourceId);
            if (!$resource) {
                throw new NotFoundHttpException("Ressource #{$resourceId} introuvable.");
            }

            $existing = $this->progressRepository->findOneBy([
                'UserRessources' => $user,
                'resource'       => $resource,
            ]);

            if ($existing) {
                throw new ConflictHttpException('Une progression existe déjà pour cette ressource.');
            }

            $data->setResource($resource);
            $data->setUserRessources($user);
            $data->setStatus('in_progress');

            if ($data->getReadPercentage() === null) {
                $data->setReadPercentage(0);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
