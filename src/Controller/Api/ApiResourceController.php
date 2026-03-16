<?php

namespace App\Controller\Api;

use App\Entity\Activity;
use App\Entity\Article;
use App\Repository\ResourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/resources', name: 'api_resources_')]
class ApiResourceController extends AbstractController
{
    public function __construct(
        private readonly ResourceRepository $resourceRepository,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $categoryId = $request->query->getInt('category') ?: null;
        $resources = $this->resourceRepository->findPublished($categoryId);

        return $this->json(array_map(fn($r) => $this->serializeList($r), $resources));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $resource = $this->resourceRepository->find($id);

        if (!$resource || $resource->getStatus() !== 'published') {
            return $this->json(['message' => 'Ressource introuvable.'], 404);
        }

        return $this->json($this->serializeDetail($resource));
    }

    private function serializeList($resource): array
    {
        return [
            'id'          => $resource->getId(),
            'title'       => $resource->getTitle(),
            'description' => $resource->getDescription(),
            'type'        => $resource->getResourceType(),
            'createdAt'   => $resource->getCreatedAt()?->format('d/m/Y'),
            'author'      => $resource->getDisplayAuthor(),
            'categories'  => $resource->getCategories()->map(fn($c) => [
                'id'   => $c->getId(),
                'name' => $c->getName(),
            ])->toArray(),
        ];
    }

    private function serializeDetail($resource): array
    {
        $data = $this->serializeList($resource);

        if ($resource instanceof Article) {
            $data['content'] = $resource->getContent();
        }

        if ($resource instanceof Activity) {
            $data['content']   = $resource->getContent();
            $data['gameType']  = $resource->getGameType();
            $data['startDate'] = $resource->getStartDate()?->format('d/m/Y');
            $data['endDate']   = $resource->getEndDate()?->format('d/m/Y');
            $data['questions'] = $resource->getQuestions()->map(fn($q) => [
                'id'           => $q->getId(),
                'question'     => $q->getQuestion(),
                'propositionA' => $q->getPropositionA(),
                'propositionB' => $q->getPropositionB(),
                'propositionC' => $q->getPropositionC(),
            ])->toArray();
        }

        $data['relatedResources'] = array_map(
            fn($r) => $this->serializeList($r),
            $this->resourceRepository->findRelated($resource->getId())
        );

        return $data;
    }
}