<?php

namespace App\EventSubscriber;

use App\Service\AuthService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AuthSubscriber implements EventSubscriberInterface
{
    public function __construct(private AuthService $authService) {}

    public function onRequestEvent(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route   = $request->attributes->get('_route');

        if (str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $publicRoutes = [
            'type_selection',
            'set_type',
            'app_login',
            'app_logout',
            'app_register',
            'api_register',
            'mdp_oublie_mvc',
            'mdp_oublie_verification_mvc',
            'mdp_oublie_verifier_code_mvc',
            '_wdt',
            '_profiler',
            '_profiler_home',
            '_profiler_search',
            '_profiler_search_bar',
            '_profiler_phpinfo',
            '_profiler_search_results',
            '_profiler_open_file',
            '_profiler_router',
            '_profiler_exception',
            '_profiler_exception_css',
        ];

        if (in_array($route, $publicRoutes, true)) {
            return;
        }

        if (!$this->authService->check()) {
            $event->setResponse(new RedirectResponse('/login'));
            return;
        }

        $isAdmin = $this->authService->isAdmin();

        if (str_starts_with($route, 'admin_') && !$isAdmin) {
            $event->setResponse(new RedirectResponse('/'));
            return;
        }

        if (str_starts_with($route, 'intervenant_') && !$isAdmin && !$this->authService->isIntervenant()) {
            $event->setResponse(new RedirectResponse('/'));
            return;
        }

        if (str_starts_with($route, 'famille') && !$isAdmin && !$this->authService->isFamille()) {
            $event->setResponse(new RedirectResponse('/'));
            return;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequestEvent',
        ];
    }
}
