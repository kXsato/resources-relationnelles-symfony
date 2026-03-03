<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Repository\ResourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResourceController extends AbstractController
{
    /**
     * PAGE DE LISTE : Affiche toutes les ressources (Cards)
     */
    #[Route('/resources', name: 'app_resource_list')]
    public function list(ResourceRepository $resourceRepository): Response
    {
        return $this->render('resource/list.html.twig', [
            // On récupère uniquement ce qui est publié
            'resources' => $resourceRepository->findBy(['status' => 'published'], ['id' => 'DESC']),
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