<?php

namespace App\Service;

use App\Entity\Principal\Famille;
use App\Entity\Principal\Intervenant;
use App\Entity\Horaire\UserSuivi;
use App\Repository\FamilleRepository;
use App\Repository\IntervenantRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;


class AuthService
{
    public const ADMIN_IDENTIFIANT = '9.99.99.99.999.999.99';

    public function __construct(
        private UserSuiviService $UserSuiviService,
        private IntervenantRepository $intervenantRepository,
        private FamilleRepository $familleRepository,
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
        $user = $this->security->getUser();
        return $user instanceof UserSuivi ? $user : null;
    }

    public function getIntervenant(): ?Intervenant
    {
        $user = $this->getUser();
        if (!$user) return null;
        return $this->intervenantRepository->findByNumSs($user->getUsername());
    }

    public function getFamille(): ?Famille
    {
        $user = $this->getUser();
        if (!$user) return null;
        return $this->familleRepository->findByNumero($user->getUsername());
    }

    public function intervenant_id(): ?int
    {
        return $this->getIntervenant()?->getId();
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
