<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiUserController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id'               => $user->getId(),
            'email'            => $user->getEmail(),
            'userName'         => $user->getUserName(),
            'birthDate'        => $user->getBirthDate()?->format('Y-m-d'),
            'registrationDate' => $user->getRegistrationDate()?->format('Y-m-d'),
            'roles'            => $user->getRoles(),
        ]);
    }

    #[Route('/api/me', name: 'api_me_update', methods: ['PUT'])]
    public function update(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (isset($data['userName'])) {
            $user->setUserName($data['userName']);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['birthDate'])) {
            $user->setBirthDate(new \DateTime($data['birthDate']));
        }

        if (isset($data['password']) && $data['password'] !== '') {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['message' => implode(', ', $messages)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->flush();

        return $this->json([
            'id'               => $user->getId(),
            'email'            => $user->getEmail(),
            'userName'         => $user->getUserName(),
            'birthDate'        => $user->getBirthDate()?->format('Y-m-d'),
            'registrationDate' => $user->getRegistrationDate()?->format('Y-m-d'),
            'roles'            => $user->getRoles(),
        ]);
    }
}
