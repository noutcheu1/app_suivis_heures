<?php

namespace App\Service;

use App\Entity\User2;
use App\Entity\Intervenant;
use App\Repository\IntervenantRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthService
{
    private ?User2 $user = null;
    private ?Intervenant $intervenant = null;

    public function __construct(
        private User2Service $user2Service,
        private IntervenantRepository $intervenantRepository,
        private SessionInterface $session
    ) {}

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
        $this->session->set('user', $user);
        $this->session->set('auth', true);

        // Chercher l'intervenant correspondant
        $intervenant = $this->intervenantRepository->findByNumSalarie($identifiant);
        if ($intervenant) {
            $this->intervenant = $intervenant;
            $this->session->set('intervenant', $intervenant);
            $this->session->set('intervenant_id', $intervenant->getId());
        }

        return true;
    }

    /**
     * Déconnecte l'utilisateur
     */
    public function logout(): void
    {
        $this->session->clear();
        $this->user = null;
        $this->intervenant = null;
    }

    /**
     * Vérifie si l'utilisateur est authentifié
     */
    public function check(): bool
    {
        return $this->session->get('auth', false);
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        // Logique à adapter selon votre système de rôles
        // Pour l'instant, on considère admin si l'identifiant commence par 'admin'
        $identifiant = $this->session->get('user')?->getIdentifiant();
        return $identifiant && str_starts_with($identifiant, 'admin');
    }

    /**
     * Retourne l'ID de l'intervenant connecté
     */
    public function intervenant_id(): ?int
    {
        return $this->session->get('intervenant_id');
    }

    /**
     * Retourne l'utilisateur connecté
     */
    public function getUser(): ?User2
    {
        return $this->session->get('user');
    }

    /**
     * Retourne l'intervenant connecté
     */
    public function getIntervenant(): ?Intervenant
    {
        return $this->session->get('intervenant');
    }

    /**
     * Définit le type d'utilisateur (FAM ou INTER)
     */
    public function setType(string $type): void
    {
        $this->session->set('type', $type);
    }

    /**
     * Retourne le type d'utilisateur
     */
    public function getType(): ?string
    {
        return $this->session->get('type');
    }
}
