<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginListener
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    #[AsEventListener]
    public function onFormLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $this->updateLastLogin($user);
    }

    #[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_success')]
    public function onJwtLogin(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $this->updateLastLogin($user);
    }

    private function updateLastLogin(User $user): void
    {
        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();
    }
}
