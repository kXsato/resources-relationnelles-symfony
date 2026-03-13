<?php

namespace App\Controller;

use App\Repository\ResourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ResourceRepository $resourceRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'latestArticles' => $resourceRepository->findPublished(null, 10),
        ]);
    }
}
