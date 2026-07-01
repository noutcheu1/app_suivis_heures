<?php

namespace App\EventSubscriber;

use App\Service\AuditLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Journalise CHAQUE action d'un utilisateur connecté (navigation + écritures) :
 * qui, quelle page/route, méthode HTTP, statut — pour la traçabilité complète.
 *
 * On ignore : sous-requêtes, ressources statiques, profiler, et les visiteurs
 * anonymes (les tentatives de connexion sont déjà tracées par AppAuthenticator).
 */
class RequestAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogger $audit,
        private Security    $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        // Priorité basse : après le traitement, on connaît la route et le statut.
        return [KernelEvents::RESPONSE => ['onResponse', -100]];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        // Bruit inutile : assets, profiler, favicon…
        if (preg_match('#^/(_(profiler|wdt)|assets|build|bundles|css|js|images|favicon)#', $path)) {
            return;
        }

        // On ne trace que les utilisateurs AUTHENTIFIÉS (anonyme = pages publiques,
        // et l'auth est déjà journalisée ailleurs).
        if ($this->security->getUser() === null) {
            return;
        }

        $this->audit->log('user_action', [
            'method' => $request->getMethod(),
            'path'   => $path,
            'route'  => $request->attributes->get('_route'),
            'status' => $event->getResponse()->getStatusCode(),
        ]);
    }
}
