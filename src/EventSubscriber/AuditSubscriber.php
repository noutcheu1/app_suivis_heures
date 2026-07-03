<?php

namespace App\EventSubscriber;

use App\Entity\Horaire\UserSuivi;
use App\Service\AuditLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Traçabilité des COMPTES (table users_suivi) via les événements Doctrine :
 * création / modification / suppression d'un UserSuivi → journal d'audit.
 *
 * Couvre automatiquement : changement d'email (account_update) et suppression de
 * compte (account_delete). Les LECTURES (data_view) ne sont pas captables ici
 * (Doctrine ne déclenche rien sur un SELECT) → appels ciblés AuditLogger::log().
 */
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
class AuditSubscriber
{
    public function __construct(private AuditLogger $audit) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->logCompte('account_create', $args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->logCompte('account_update', $args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->logCompte('account_delete', $args->getObject());
    }

    /** On ne trace ici que les comptes (évite le bruit des autres entités métier). */
    private function logCompte(string $event, object $entity): void
    {
        if (!$entity instanceof UserSuivi) {
            return;
        }
        $this->audit->log($event, [
            'target'        => 'user:' . $entity->getUserIdentifier(),
            'compte_role'   => implode(',', $entity->getRoles()),
        ]);
    }
}
