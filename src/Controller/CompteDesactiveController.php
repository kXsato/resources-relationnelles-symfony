<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CompteDesactiveController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/compte-desactive', name: 'app_compte_desactive')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return $this->render('compte_desactive/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/compte-desactive/demande-reactivation', name: 'app_compte_desactive_request', methods: ['POST'])]
    public function requestReactivation(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reactivation_request', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if ($user->getReactivationRequestedAt() === null) {
            $user->setReactivationRequestedAt(new \DateTime());
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_compte_desactive');
    }
}
