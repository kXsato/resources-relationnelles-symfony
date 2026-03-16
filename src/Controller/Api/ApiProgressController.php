<?php

namespace App\Controller\Api;

use App\Entity\UserRessourceProgress;
use App\Repository\ResourceRepository;
use App\Repository\UserRessourceProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/progress', name: 'api_progress_')]
class ApiProgressController extends AbstractController
{
    public function __construct(
        private readonly UserRessourceProgressRepository $progressRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {}

    /**
     * GET /api/progress
     * Retourne toutes les progressions de l'utilisateur connecté.
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $entries = $this->progressRepository->findBy(['UserRessources' => $user]);

        return $this->json(array_map(fn(UserRessourceProgress $p) => $this->serialize($p), $entries));
    }

    /**
     * GET /api/progress/{id}
     * Retourne une progression spécifique.
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(UserRessourceProgress $progress): JsonResponse
    {
        if (!$this->isOwner($progress)) {
            return $this->json(['message' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($this->serialize($progress));
    }

    /**
     * POST /api/progress
     * Crée une nouvelle entrée de progression.
     * Body JSON : { "resourceId": 1, "readPercentage": 10 }
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, ResourceRepository $resourceRepository): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['resourceId'])) {
            return $this->json(['message' => 'Le champ resourceId est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $resource = $resourceRepository->find($data['resourceId']);
        if (!$resource) {
            return $this->json(['message' => 'Ressource introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $existing = $this->progressRepository->findOneBy([
            'UserRessources' => $user,
            'resource'       => $resource,
        ]);
        if ($existing) {
            return $this->json(['message' => 'Une progression existe déjà pour cette ressource.'], Response::HTTP_CONFLICT);
        }

        $progress = new UserRessourceProgress();
        $progress->setUserRessources($user);
        $progress->setResource($resource);
        $progress->setStatus('in_progress');
        $progress->setReadPercentage((int) ($data['readPercentage'] ?? 0));

        $this->entityManager->persist($progress);
        $this->entityManager->flush();

        return $this->json($this->serialize($progress), Response::HTTP_CREATED);
    }

    /**
     * PATCH /api/progress/{id}
     * Met à jour le pourcentage de lecture.
     * Body JSON : { "readPercentage": 75 }
     */
    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(UserRessourceProgress $progress, Request $request): JsonResponse
    {
        if (!$this->isOwner($progress)) {
            return $this->json(['message' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['readPercentage'])) {
            return $this->json(['message' => 'Le champ readPercentage est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $progress->setReadPercentage((int) $data['readPercentage']);

        $this->entityManager->flush();

        return $this->json($this->serialize($progress));
    }

    /**
     * DELETE /api/progress/{id}
     * Supprime une entrée de progression.
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(UserRessourceProgress $progress): JsonResponse
    {
        if (!$this->isOwner($progress)) {
            return $this->json(['message' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($progress);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function isOwner(UserRessourceProgress $progress): bool
    {
        return $progress->getUserRessources() === $this->security->getUser();
    }

    private function serialize(UserRessourceProgress $p): array
    {
        return [
            'id'             => $p->getId(),
            'status'         => $p->getStatus(),
            'readPercentage' => $p->getReadPercentage(),
            'completeAt'     => $p->getCompleteAt()?->format('Y-m-d H:i:s'),
            'resource'       => [
                'id'    => $p->getResource()?->getId(),
                'title' => $p->getResource()?->getTitle(),
                'slug'  => $p->getResource()?->getSlug(),
            ],
        ];
    }
}
