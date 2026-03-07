<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Favorite;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/favorites')]
class FavoriteController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'api_favorite_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggle(Article $article, FavoriteRepository $favoriteRepository, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $existing = $favoriteRepository->findOneBy(['user' => $user, 'article' => $article]);

        if ($existing) {
            $em->remove($existing);
            $em->flush();
            return $this->json(['status' => 'removed']);
        }

        $favorite = new Favorite();
        $favorite->setUser($user);
        $favorite->setArticle($article);
        $favorite->setCreatedAt(new \DateTimeImmutable());
        $em->persist($favorite);
        $em->flush();

        return $this->json(['status' => 'added']);
    }

    #[Route('/list', name: 'api_favorite_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(FavoriteRepository $favoriteRepository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $favorites = $favoriteRepository->findBy(['user' => $user]);

        $data = array_map(fn(Favorite $f) => [
            'id'        => $f->getArticle()->getId(),
            'title'     => $f->getArticle()->getTitle(),
            'createdAt' => $f->getCreatedAt()->format('d/m/Y'),
        ], $favorites);

        return $this->json($data);
    }
}