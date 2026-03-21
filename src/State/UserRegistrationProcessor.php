<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof User) {
            $errors = $this->validator->validate($data, null, ['Default', 'user:write']);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                throw new UnprocessableEntityHttpException(implode(', ', $messages));
            }

            if ($data->getPlainPassword()) {
                $data->setPassword($this->passwordHasher->hashPassword($data, $data->getPlainPassword()));
                $data->setPlainPassword(null);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}