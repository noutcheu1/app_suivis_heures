<?php

namespace App\Entity\Horaire;

use App\Repository\UserSuiviRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserSuiviRepository::class)]
#[ORM\Table(name: 'users_suivi')]
class UserSuivi implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Login de connexion — contient selon le rôle :
     *   admin       → identifiant libre
     *   intervenant → numéro SS (numSS_Candidats)
     *   famille     → code PM_Famille ou PGE_Famille
     */
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $username;

    #[ORM\Column(type: 'string', length: 20)]
    private string $role; // 'admin' | 'intervenant' | 'famille'

    /** Email du dossier (candidat / famille), renseigné à l'inscription. */
    #[ORM\Column(name: 'email', type: 'string', length: 180, nullable: true)]
    private ?string $email = null;

    /** Téléphone normalisé (chiffres only) — sert au pointage QR sans connexion. */
    #[ORM\Column(name: 'telephone', type: 'string', length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\Column(name: 'token_reinit', type: 'string', length: 64, nullable: true)]
    private ?string $tokenReinit = null;

    #[ORM\Column(name: 'token_expire', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $tokenExpire = null;

    #[ORM\Column(name: 'cree_le', type: 'datetime')]
    private \DateTimeInterface $creeLe;

    public function __construct()
    {
        $this->creeLe = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): static { $this->username = $username; return $this; }

    public function getRoles(): array
    {
        return match (strtolower($this->role)) {
            'admin' => ['ROLE_ADMIN'],
            'intervenant' => ['ROLE_INTERVENANT'],
            'famille' => ['ROLE_FAMILLE'],
            default => ['ROLE_USER'],
        };
    }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getTokenReinit(): ?string { return $this->tokenReinit; }
    public function setTokenReinit(?string $token): static { $this->tokenReinit = $token; return $this; }

    public function getTokenExpire(): ?\DateTimeInterface { return $this->tokenExpire; }
    public function setTokenExpire(?\DateTimeInterface $dt): static { $this->tokenExpire = $dt; return $this; }

    public function getCreeLe(): \DateTimeInterface { return $this->creeLe; }

    // ── UserInterface ────────────────────────────────────────────
    public function getUserIdentifier(): string { return $this->username; }


    public function eraseCredentials(): void {}
}
