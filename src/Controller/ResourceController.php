<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Article;
use App\Entity\UserRessourceProgress;
use App\Repository\CategoryRepository;
use App\Repository\ResourceRepository;
use App\Repository\UserRessourceProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResourceController extends AbstractController
{
    #[Route('/resources', name: 'app_resource_list')]
    public function list(Request $request, ResourceRepository $resourceRepository, CategoryRepository $categoryRepository): Response
    {
        $categoryId = $request->query->getInt('category') ?: null;

        return $this->render('resource/list.html.twig', [
            'resources'         => $resourceRepository->findPublished($categoryId),
            'categories'        => $categoryRepository->findAll(),
            'currentCategoryId' => $categoryId,
        ]);
    }

    #[Route('/resources/{id}', name: 'app_resource_show')]
    public function show(int $id, ResourceRepository $resourceRepository, Security $security, UserRessourceProgressRepository $progressRepository, EntityManagerInterface $em): Response
    {
        $resource = $resourceRepository->find($id);

        if (!$resource || $resource->getStatus() !== 'published') {
            throw $this->createNotFoundException("Cette ressource n'est pas disponible.");
        }

        $progress = null;
        $user = $security->getUser();
        if ($user) {
            $progress = $progressRepository->findOneBy([
                'UserRessources' => $user,
                'resource'       => $resource,
            ]);

            if (!$progress) {
                $progress = new UserRessourceProgress();
                $progress->setUserRessources($user);
                $progress->setResource($resource);
                $progress->setStatus('in_progress');
                $progress->setReadPercentage(0);
                $em->persist($progress);
                $em->flush();
            }
        }

        // Choisir le bon template selon le type de ressource
        if ($resource instanceof Activity) {
            $template = 'resource/activity_show.html.twig';
        } else {
            $template = 'resource/article_show.html.twig';
        }

        return $this->render($template, [
            'article'         => $resource,
            'progress'        => $progress,
            'relatedArticles' => $resourceRepository->findRelated($resource->getId()),
        ]);
    }
}