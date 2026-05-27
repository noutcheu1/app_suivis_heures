<?php

namespace App\Twig;

use App\Service\AuthService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AuthExtension extends AbstractExtension
{
    public function __construct(private AuthService $authService) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('auth_intervenant_id', fn() => $this->authService->intervenant_id()),
            new TwigFunction('auth_famille_id', fn() => $this->authService->famille_id()),
        ];
    }
}
