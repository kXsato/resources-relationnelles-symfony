<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CrudControllerExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_crud_controller', fn(string $fqcn, string $interface) => is_a($fqcn, $interface, true)),
        ];
    }
}
