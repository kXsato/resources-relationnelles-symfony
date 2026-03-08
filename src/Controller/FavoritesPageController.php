<?php

namespace App\Controller;

use App\Repository\FavoriteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mes-favoris', name: 'app_favorites')]
#[IsGranted('ROLE_USER')]
class FavoritesPageController extends AbstractController
{
    public function __invoke(FavoriteRepository $favoriteRepository): Response
    {
        $favorites = $favoriteRepository->findBy(['user' => $this->getUser()]);

        return $this->render('favorite/index.html.twig', [
            'favorites' => $favorites,
        ]);
    }
}