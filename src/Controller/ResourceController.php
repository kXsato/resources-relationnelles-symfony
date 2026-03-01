<?php

namespace App\Controller;

use App\Entity\Article; // N'oublie pas l'import de ton entité
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResourceController extends AbstractController
{
    // On ajoute {id} à la route pour identifier l'article
    #[Route('/resource/{id}', name: 'app_resource')]
    public function index(Article $article): Response
    {
        // Sécurité : on vérifie que l'article est bien publié
        if ($article->getStatus() !== 'published') {
            throw $this->createNotFoundException("Cet article n'est pas disponible.");
        }

        return $this->render('resource/article_show.html.twig', [
            'article' => $article,
        ]);
    }
}