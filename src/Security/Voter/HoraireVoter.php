<?php

namespace App\Security\Voter;

use App\Entity\Horaire\Horaireinter;
use App\Service\AuthService;
use App\Service\HoraireinterService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Garde d'accès sur les prestations (équivalent des "route guards") :
 * centralise les règles de verrouillage avant création / modification / suppression.
 *
 *  - Période déjà SIGNÉE (ce mois + service) → figée pour tout le monde sauf admin.
 *  - Mois CLÔTURÉ (hors fenêtre de saisie) → pas de modification/suppression sauf admin.
 *
 * Le subject est une Horaireinter (transitoire pour la création : numInter + date + type).
 */
final class HoraireVoter extends Voter
{
    public const CREATE = 'HORAIRE_CREATE';
    public const EDIT   = 'HORAIRE_EDIT';
    public const DELETE = 'HORAIRE_DELETE';

    public function __construct(
        private AuthService $authService,
        private HoraireinterService $horaireService,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::CREATE, self::EDIT, self::DELETE], true)
            && $subject instanceof Horaireinter;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var Horaireinter $h */
        $h = $subject;

        // L'admin n'est pas soumis au verrouillage / à la signature.
        if ($this->authService->isAdmin()) {
            return true;
        }

        $date = $h->getDatePresta();
        $type = (string) $h->getTypePresta();
        $num  = (int) $h->getNumInter();

        // Période signée → figée (uniquement ce mois/service concerné).
        if ($date && $this->horaireService->estPeriodeSignee($num, $date, $type)) {
            return false;
        }

        // Modification / suppression : interdit si le mois est clôturé.
        if (($attribute === self::EDIT || $attribute === self::DELETE)
            && $this->horaireService->isVerrouille($h)) {
            return false;
        }

        return true;
    }
}
