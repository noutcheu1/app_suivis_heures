<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Journal d'AUDIT de sécurité (traçabilité RGPD).
 *
 * Écrit dans le canal Monolog « audit » → fichier var/log/audit.log (JSON, horodaté).
 * Capture automatiquement, pour chaque événement : date/heure (par Monolog), IP source,
 * user-agent/device, et l'utilisateur courant. Les identifiants sensibles (téléphone,
 * numSS saisi) doivent être passés via hashId() pour ne jamais stocker la donnée en clair.
 */
class AuditLogger
{
    public function __construct(
        private LoggerInterface $auditLogger,   // canal « audit » (autowiring par le nom $auditLogger)
        private RequestStack    $requestStack,
        private Security        $security,
    ) {}

    /**
     * Enregistre un événement d'audit.
     *
     * @param string               $event   ex. login_success, login_failed, logout,
     *                                       password_change, email_change, account_delete,
     *                                       data_view, data_modify
     * @param array<string,mixed>  $context acteur/cible/détails éventuels (surchargent les valeurs auto)
     */
    public function log(string $event, array $context = []): void
    {
        $req   = $this->requestStack->getCurrentRequest();
        $token = $this->security->getToken();
        $user  = $this->security->getUser();

        // Nom lisible mémorisé en session au login (prénom nom / « Administrateur »).
        $nomSession = ($req && $req->hasSession() && $req->getSession()->isStarted())
            ? $req->getSession()->get('audit_nom')
            : null;

        $this->auditLogger->info($event, array_merge([
            'event'      => $event,
            'actor'      => $nomSession ?? $user?->getUserIdentifier() ?? 'anonyme',
            'compte'     => $user?->getUserIdentifier(),
            'actor_role' => $token ? implode(',', $token->getRoleNames()) : 'anonyme',
            'ip'         => $req?->getClientIp(),
            'user_agent' => $req?->headers->get('User-Agent'),
        ], $context));
    }

    /** Hache un identifiant sensible (téléphone, numSS saisi…) avant journalisation. */
    public static function hashId(?string $id): string
    {
        return ($id === null || $id === '') ? '(vide)' : hash('sha256', $id);
    }
}
