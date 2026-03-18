<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\ResetPasswordProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/reset-password/reset',
            processor: ResetPasswordProcessor::class,
            output: false,
            status: 200,
        ),
    ],
    security: "is_granted('PUBLIC_ACCESS')",
)]
class ResetPasswordResource
{
    #[Assert\NotBlank(message: 'Le token est requis.')]
    public string $token = '';

    #[Assert\NotBlank(message: 'Veuillez saisir un mot de passe.')]
    #[Assert\Length(min: 6, max: 15, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le mot de passe ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(pattern: '/[A-Z]/', message: 'Le mot de passe doit contenir au moins une majuscule.')]
    #[Assert\Regex(pattern: '/\d/', message: 'Le mot de passe doit contenir au moins un chiffre.')]
    public string $password = '';
}
