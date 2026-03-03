<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Repository\CategoryRepository;
use App\Repository\ResourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResourceController extends AbstractController
{
    /**
     * PAGE DE LISTE : Affiche toutes les ressources (Cards)
     */
    #[Route('/resources', name: 'app_resource_list')]
    public function list(Request $request, ResourceRepository $resourceRepository, CategoryRepository $categoryRepository): Response
    {
        $categoryId = $request->query->getInt('category') ?: null;

        return $this->render('resource/list.html.twig', [
            'resources'          => $resourceRepository->findPublished($categoryId),
            'categories'         => $categoryRepository->findAll(),
            'currentCategoryId'  => $categoryId,
        ]);
    }

    /**
     * PAGE DE DÉTAILS : Affiche une ressource spécifique
     */
    #[Route('/resources/{id}', name: 'app_resource_show')]
    public function show(Resource $resource): Response
    {
        if ($resource->getStatus() !== 'published') {
            throw $this->createNotFoundException("Cette ressource n'est pas disponible.");
        }

        return $this->render('resource/article_show.html.twig', [
            'article' => $resource,
        ]);
    }
}
