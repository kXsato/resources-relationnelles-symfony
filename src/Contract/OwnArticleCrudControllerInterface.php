<?php

namespace App\Contract;

/**
 * Tout controller implémentant cette interface indique qu'il gère les articles
 * dont l'auteur est l'utilisateur connecté (brouillon, soumission, consultation).
 * Utilisé notamment dans les templates Twig pour adapter l'affichage
 * (motif de rejet, boutons d'action) sans dépendre d'une classe concrète.
 */
interface OwnArticleCrudControllerInterface
{
}
