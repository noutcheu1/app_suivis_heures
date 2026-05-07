<?php

namespace App\Service;

use App\Entity\Famille;
use App\Entity\Intervenant;
use App\Entity\User2;
use App\Repository\FamilleRepository;
use App\Repository\IntervenantRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthService
{
    public const ADMIN_IDENTIFIANT = '9.99.99.99.999.999.99';

    public function __construct(
        private User2Service $user2Service,
        private IntervenantRepository $intervenantRepository,
        private FamilleRepository $familleRepository,
        private RequestStack $requestStack
    ) {}

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    public function login(string $identifiant, string $motDePasse): bool
    {
        $user = $this->user2Service->authentifier($identifiant, $motDePasse);

        if (!$user) {
            return false;
        }

        $session = $this->getSession();
        $session->set('user', $user);
        $session->set('auth', true);

        if ($identifiant === self::ADMIN_IDENTIFIANT) {
            $session->set('type', 'ADMIN');
        } else {
            $intervenant = $this->intervenantRepository->findByNumSs($identifiant);
            if ($intervenant) {
                $session->set('intervenant', $intervenant);
                $session->set('intervenant_id', $intervenant->getId());
                $session->set('type', 'INTER');
            } else {
                $famille = $this->familleRepository->findByNumero($identifiant);
                if ($famille) {
                    $session->set('famille', $famille);
                    $session->set('famille_id', $famille->getNumeroFamille());
                }
                $session->set('type', 'FAM');
            }
        }

        $session->save();

        return true;
    }

    public function logout(): void
    {
        $this->getSession()->clear();
    }

    public function check(): bool
    {
        return $this->getSession()->get('auth', false);
    }

    public function isAdmin(): bool
    {
        return $this->getSession()->get('user')?->getIdentifiant() === self::ADMIN_IDENTIFIANT;
    }

    public function intervenant_id(): ?int
    {
        return $this->getSession()->get('intervenant_id');
    }

    public function famille_id(): ?string
    {
        return $this->getSession()->get('famille_id');
    }

    public function getUser(): ?User2
    {
        return $this->getSession()->get('user');
    }

    public function getIntervenant(): ?Intervenant
    {
        return $this->getSession()->get('intervenant');
    }

    public function getFamille(): ?Famille
    {
        return $this->getSession()->get('famille');
    }

    public function setType(string $type): void
    {
        $this->getSession()->set('type', $type);
    }

    public function getType(): ?string
    {
        return $this->getSession()->get('type');
    }

    public function isIntervenant(): bool
    {
        return $this->getSession()->get('type') === 'INTER';
    }

    public function isFamille(): bool
    {
        return $this->getSession()->get('type') === 'FAM';
    }
}
