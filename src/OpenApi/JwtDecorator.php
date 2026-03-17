<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class JwtDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $securitySchemes = $openApi->getComponents()->getSecuritySchemes() ?? new ArrayObject();
        $securitySchemes['bearerAuth'] = new SecurityScheme(
            type: 'http',
            scheme: 'bearer',
            bearerFormat: 'JWT',
        );

        return $openApi
            ->withComponents($openApi->getComponents()->withSecuritySchemes($securitySchemes))
            ->withSecurity([['bearerAuth' => []]]);
    }
}
