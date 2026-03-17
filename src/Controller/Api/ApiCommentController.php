<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\Resource;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/comments')]
class ApiCommentController extends AbstractController
{
    #[Route('/resource/{id}', name: 'api_comment_list', methods: ['GET'])]
    public function list(Resource $resource, CommentRepository $commentRepository): JsonResponse
    {
        try {
            $comments = $commentRepository->findRootComments($resource);
            $data = array_map(fn(Comment $c) => $this->formatComment($c), $comments);
            return $this->json($data);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/add', name: 'api_comment_add', methods: ['POST'])]
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

    #[Route('/report/{id}', name: 'api_comment_report', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function report(Comment $comment, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $motif = $data['motif'] ?? 'Non précisé';

        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        $userName = $currentUser->getUserName();

        if ($comment->hasReportedBy($userName)) {
            return $this->json(['status' => 'already_reported']);
        }

        $comment->addReport($userName, $motif);
        $em->flush();

        return $this->json(['status' => 'reported', 'count' => $comment->getReportCount()]);
    }

    #[Route('/delete/{id}', name: 'api_comment_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Comment $comment, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!($user instanceof \App\Entity\User && $comment->isOwnedBy($user)) && !$this->isGranted('ROLE_MODERATOR')) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        foreach ($comment->getChildren() as $child) {
            $em->remove($child);
        }

        $em->remove($comment);
        $em->flush();

        return $this->json(['status' => 'deleted']);
    }

    private function formatComment(Comment $comment): array
    {
        $author = $comment->getUser();
        $authorName = ($author !== null && $author->isAccountActivated())
            ? $author->getUserName()
            : 'Anonyme';

        $currentUser = $this->getUser();
        $isOwner = false;
        if ($currentUser instanceof \App\Entity\User) {
            $isOwner = $comment->isOwnedBy($currentUser);
        }

        return [
            'id'          => $comment->getId(),
            'content'     => $comment->getContent(),
            'createdAt'   => $comment->getCreatedAt()->format('d/m/Y H:i'),
            'author'      => $authorName,
            'isOwner'     => $isOwner,
            'isModerator' => $this->isGranted('ROLE_MODERATOR'),
            'reportCount' => $comment->getReportCount(),
            'reports'     => $comment->getReports(),
            'children'    => array_map(fn(Comment $c) => $this->formatComment($c), $comment->getChildren()->toArray()),
        ];
    }
}