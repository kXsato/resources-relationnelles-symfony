<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class DeactivatedAccountSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_PATHS = [
        '/compte-desactive',
        '/login',
        '/logout',
    ];

    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        // Les routes /api ne doivent jamais être redirigées :
        // - les endpoints non authentifiés (ex: /api/login_check) n'ont pas de token
        // - les endpoints authentifiés portent un header Authorization
        // Dans les deux cas, l'app mobile gère le compte désactivé via isAccountActivated.
        if (str_starts_with($path, '/api')) {
            return;
        }

        foreach (self::ALLOWED_PATHS as $allowed) {
            if (str_starts_with($path, $allowed)) {
                return;
            }
        }

        // Ignorer les routes internes Symfony (profiler, assets...)
        if (str_starts_with($path, '/_')) {
            return;
        }

        $user = $this->security->getUser();

        if ($user instanceof User && !$user->isAccountActivated()) {
            $event->setResponse(
                new RedirectResponse($this->router->generate('app_compte_desactive'))
            );
        }
    }
}
