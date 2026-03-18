<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ResetPasswordResource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        /** @var ResetPasswordResource $data */
        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($data->token);
        } catch (ResetPasswordExceptionInterface $e) {
            throw new BadRequestHttpException($e->getReason());
        }

        $this->resetPasswordHelper->removeResetRequest($data->token);

        $user->setPassword($this->passwordHasher->hashPassword($user, $data->password));
        $this->entityManager->flush();

        return null;
    }
}
