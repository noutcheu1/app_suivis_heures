<?php

namespace App\EventSubscriber;

use App\Service\AuditLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Journalise les DÉCONNEXIONS dans le journal d'audit.
 * On lit l'utilisateur depuis le token de l'événement (la session est en cours de purge).
 */
class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(private AuditLogger $audit) {}

    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $this->audit->log('logout', [
            'actor'      => $token?->getUserIdentifier() ?? 'anonyme',
            'actor_role' => $token ? implode(',', $token->getRoleNames()) : 'anonyme',
        ]);
    }
}
