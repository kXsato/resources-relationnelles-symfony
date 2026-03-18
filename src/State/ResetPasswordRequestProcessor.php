<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ResetPasswordRequestResource;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        /** @var ResetPasswordRequestResource $data */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data->email]);

        // Ne pas révéler si l'email existe ou non
        if (!$user) {
            return null;
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            return null;
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@resources-relationnelles.com', 'Ressources Relationnelles'))
            ->to((string) $user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context(['resetToken' => $resetToken]);

        $this->mailer->send($email);

        return null;
    }
}
