<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Resource;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comments')]
class CommentController extends AbstractController
{
    #[Route('/resource/{id}', name: 'comment_list', methods: ['GET'])]
    public function list(Resource $resource, CommentRepository $commentRepository): JsonResponse
    {
        $comments = $commentRepository->findRootComments($resource);

        $data = array_map(fn(Comment $c) => $this->formatComment($c), $comments);

        return $this->json($data);
    }

    #[Route('/add', name: 'comment_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(Request $request, EntityManagerInterface $em, CommentRepository $commentRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $resource = $em->find(Resource::class, $data['resourceId']);
        if (!$resource) {
            return $this->json(['error' => 'Ressource introuvable'], 404);
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setResource($resource);
        $comment->setUser($this->getUser());
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setIsPublished(true);

        if (!empty($data['parentId'])) {
            $parent = $commentRepository->find($data['parentId']);
            if ($parent) {
                $comment->setParent($parent);
            }
        }

        $em->persist($comment);
        $em->flush();

        return $this->json($this->formatComment($comment));
    }

    #[Route('/delete/{id}', name: 'comment_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Comment $comment, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$comment->isOwnedBy($user) && !$this->isGranted('ROLE_MODERATOR')) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $em->remove($comment);
        $em->flush();

        return $this->json(['status' => 'deleted']);
    }

    private function formatComment(Comment $comment): array
    {
        $author = $comment->getUser();
        $isActive = $author?->isAccountActivated() ?? false;

        return [
            'id'          => $comment->getId(),
            'content'     => $comment->getContent(),
            'createdAt'   => $comment->getCreatedAt()->format('d/m/Y H:i'),
            'author'      => $isActive ? $author->getUserName() : 'Anonyme',
            'isOwner'     => $comment->isOwnedBy($this->getUser()),
            'isModerator' => $this->isGranted('ROLE_MODERATOR'),
            'children'    => array_map(fn(Comment $c) => $this->formatComment($c), $comment->getChildren()->toArray()),
        ];
    }
}