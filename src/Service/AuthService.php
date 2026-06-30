<?php

namespace App\Service;

use App\Entity\Principal\Famille;
use App\Entity\Principal\Intervenant;
use App\Entity\Horaire\UserSuivi;
use App\Repository\CandidatRepository;
use App\Repository\FamilleRepository;
use App\Repository\IntervenantRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;


class AuthService
{
    public const ADMIN_IDENTIFIANT = '9999999999';

    public function __construct(
        private UserSuiviService $UserSuiviService,
        private IntervenantRepository $intervenantRepository,
        private FamilleRepository $familleRepository,
        private CandidatRepository $candidatRepository,
        private Security $security,
        private RequestStack $requestStack
    ) {}

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    public function check(): bool
    {
        return $this->security->isGranted('IS_AUTHENTICATED_FULLY');
    }

    public function isAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    public function isIntervenant(): bool
    {
        return $this->security->isGranted('ROLE_INTERVENANT');
    }

    public function isFamille(): bool
    {
        return $this->security->isGranted('ROLE_FAMILLE');
    }

    public function getRole(): ?string
    {
        if ($this->isAdmin())       return 'admin';
        if ($this->isIntervenant()) return 'intervenant';
        if ($this->isFamille())     return 'famille';
        return null;
    }

    public function getUser(): ?UserSuivi
    {
         
        return $this->security->getUser();
    }

    public function getIntervenant(): ?Intervenant
    {
        $user = $this->getUser();
        if (!$user) return null;

        $username = $user->getUserIdentifier();

        // Approche principale : trouver le Candidat par numSS (avec normalisation
        // de format), puis charger l'Intervenant via la FK intervenants → candidats.
        $candidat = $this->candidatRepository->findByNumSs($username);
        if ($candidat?->getId()) {
            $intervenant = $this->intervenantRepository->findByCandidatId($candidat->getId());
            if ($intervenant) return $intervenant;
        }
        // Fallback : recherche directe dans vue_intervenants (plusieurs formats)
        return $this->intervenantRepository->findByAnyIdentifier($username);
    }


    public function getFamille(): ?Famille
    {
        $user = $this->getUser();
        if (!$user) return null;
        return $this->familleRepository->findByNumero($user->getUserIdentifier());
    }

    public function intervenant_id(): ?int
    {
        return $this->getIntervenant()?->getId();
    }

    /**
     * Returns true only when the logged-in user is linked to an intervenant
     * whose candidature has been accepted (not 'En attente').
     */
    public function isIntervenantAccepted(): bool
    {
        return $this->getIntervenant()?->isAccepted() ?? false;
    }

    /**
     * Returns true when the intervenant is not archived (permanently or temporarily today).
     */
    public function isIntervenantActif(): bool
    {
        $iv = $this->getIntervenant();
        if (!$iv) return false;
        if ($iv->getArchive()) return false;
        if ($iv->getArchiveTemporaire()) {
            $today = new \DateTime('today');
            $debut = $iv->getDateDebutArchiveTemporaire();
            $fin   = $iv->getDateFinArchiveTemporaire();
            if ($debut && $today >= $debut && (!$fin || $today <= $fin)) {
                return false;
            }
        }
        return true;
    }

    public function famille_id(): ?string
    {
        return $this->getFamille()?->getNumeroFamille();
    }

    public function setType(string $type): void
    {
        $this->getSession()->set('type', $type);
    }

    public function getType(): ?string
    {
        return $this->getSession()->get('type');
    }
}
