<?php

namespace App\EventSubscriber;

use App\Security\Auth;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AuthSubscriber implements EventSubscriberInterface
{
    public function onRequestEvent(RequestEvent $event): void
    {
        $request = $event->getRequest();
        

        if (str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $session = $request->getSession();
        

        // routes publiques
        $publicRoutes = [
            'type_selection',
            'set_type',
            'app_login',
            'app_logout',
            'app_register',
            'set_type',
            '_wdt',
            '_profiler',
        ];

        $route = $request->attributes->get('_route');

        if (in_array($route, $publicRoutes, true)) {
            return;
        }

        $route = $request->attributes->get('_route');
        $auth = new Auth($request->getSession());

        if (str_starts_with($route, 'admin_') && !$auth->isAdmin()) {
            $event->setResponse(new RedirectResponse('/'));
        }

        if (str_starts_with($route, 'intervenant_') && !$auth->is_intervenant()) {
            $event->setResponse(new RedirectResponse('/'));
        }

        if (str_starts_with($route, 'famille') && !$auth->is_famille()) {
            $event->setResponse(new RedirectResponse('/'));
        }
        
        if (!$session || !$session->has('username')) {
            $event->setResponse(new RedirectResponse('/login'));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequestEvent',
        ];
    }
}
