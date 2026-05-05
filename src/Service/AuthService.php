<?php

namespace App\Service;

use App\Entity\User2;
use App\Entity\Intervenant;
use App\Repository\IntervenantRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthService
{
    private ?User2 $user = null;
    private ?Intervenant $intervenant = null;

    public function __construct(
        private User2Service $user2Service,
        private IntervenantRepository $intervenantRepository,
        private RequestStack $requestStack
    ) {}

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    /**
     * Authentifie un utilisateur
     */
    public function login(string $identifiant, string $motDePasse): bool
    {
        $user = $this->user2Service->authentifier($identifiant, $motDePasse);
        
        if (!$user) {
            return false;
        }

        $this->user = $user;
        $this->getSession()->set('user', $user);
        $this->getSession()->set('auth', true);

        // Définir le type d'utilisateur
        if ($identifiant === '9.99.99.99.999.999.99') {
            $this->getSession()->set('type', 'ADMIN');
        } else {
            // Chercher l'intervenant correspondant
            $intervenant = $this->intervenantRepository->findByNumSalarie($identifiant);
            if ($intervenant) {
                $this->intervenant = $intervenant;
                $this->getSession()->set('intervenant', $intervenant);
                $this->getSession()->set('intervenant_id', $intervenant->getId());
                $this->getSession()->set('type', 'INTER');
            } else {
                // Si c'est pas un intervenant, c'est une famille
                $this->getSession()->set('type', 'FAM');
            }
        }

        return true;
    }

    /**
     * Déconnecte l'utilisateur
     */
    public function logout(): void
    {
        $this->getSession()->clear();
        $this->user = null;
        $this->intervenant = null;
    }

    /**
     * Vérifie si l'utilisateur est authentifié
     */
    public function check(): bool
    {
        return $this->getSession()->get('auth', false);
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        $identifiant = $this->getSession()->get('user')?->getIdentifiant();
        return $identifiant === '9.99.99.99.999.999.99';
    }

    /**
     * Retourne l'ID de l'intervenant connecté
     */
    public function intervenant_id(): ?int
    {
        return $this->getSession()->get('intervenant_id');
    }

    /**
     * Retourne l'utilisateur connecté
     */
    public function getUser(): ?User2
    {
        return $this->getSession()->get('user');
    }

    /**
     * Retourne l'intervenant connecté
     */
    public function getIntervenant(): ?Intervenant
    {
        return $this->getSession()->get('intervenant');
    }

    /**
     * Définit le type d'utilisateur (FAM ou INTER)
     */
    public function setType(string $type): void
    {
        $this->getSession()->set('type', $type);
    }

    /**
     * Retourne le type d'utilisateur
     */
    public function getType(): ?string
    {
        return $this->getSession()->get('type');
    }

    /**
     * Vérifie si l'utilisateur est un intervenant
     */
    public function isIntervenant(): bool
    {
        $type = $this->getSession()->get('type');
        return $type === 'INTER';
    }

    /**
     * Vérifie si l'utilisateur est une famille
     */
    public function isFamille(): bool
    {
        $type = $this->getSession()->get('type');
        return $type === 'FAM';
    }
}
