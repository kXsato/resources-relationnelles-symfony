<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\ResetPasswordRequestProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/reset-password/request',
            processor: ResetPasswordRequestProcessor::class,
            output: false,
            status: 200,
        ),
    ],
    security: "is_granted('PUBLIC_ACCESS')",
)]
class ResetPasswordRequestResource
{
    #[Assert\NotBlank(message: 'Veuillez saisir votre adresse email.')]
    #[Assert\Email(message: 'Adresse email invalide.')]
    public string $email = '';
}
