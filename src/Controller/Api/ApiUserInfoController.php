<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/user', name: 'api_user_')]
class ApiUserInfoController extends AbstractController
{
    public function __construct(
        private readonly Security $security,
    ) {}

    /**
     * GET /api/user/me
     * Retourne les informations de l'utilisateur connecté.
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($this->serialize($user));
    }

    private function serialize(User $user): array
    {
        return [
            'id'               => $user->getId(),
            'email'            => $user->getEmail(),
            'userName'         => $user->getUserName(),
            'roles'            => $user->getRoles(),
            'birthDate'        => $user->getBirthDate()?->format('Y-m-d'),
            'registrationDate' => $user->getRegistrationDate()?->format('Y-m-d H:i:s'),
            'lastLogin'        => $user->getLastLogin()?->format('Y-m-d H:i:s'),
            'isAccountActivated' => $user->isAccountActivated(),
        ];
    }
}
