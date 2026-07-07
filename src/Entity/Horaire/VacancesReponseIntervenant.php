<?php

namespace App\Entity\Horaire;

use App\Repository\VacancesReponseIntervenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Réponse d'UN intervenant à une campagne de congés (VacancesConfig).
 * Créée (vide, avec un jeton) à l'envoi des emails, puis remplie via le lien.
 */
#[ORM\Entity(repositoryClass: VacancesReponseIntervenantRepository::class)]
#[ORM\Table(name: 'vacances_reponse_intervenant')]
#[ORM\UniqueConstraint(name: 'uniq_vac_inter', columns: ['vacancesConfigId', 'numInter'])]
class VacancesReponseIntervenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Campagne concernée (id de VacancesConfig). */
    #[ORM\Column]
    private int $vacancesConfigId = 0;

    /** Numéro salarié de l'intervenant (PK vue_intervenants). */
    #[ORM\Column]
    private int $numInter = 0;

    /** Jeton unique du lien (magic link) → identifie la réponse sans connexion. */
    #[ORM\Column(length: 64, unique: true)]
    private string $token = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateDebutConge = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateFinConge = null;

    /** L'intervenant est-il disponible pour faire des remplacements ? */
    #[ORM\Column(nullable: true)]
    private ?bool $disponibleRemplacement = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column]
    private bool $repondu = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $reponduLe = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->token = bin2hex(random_bytes(24));
    }

    public function getId(): ?int { return $this->id; }

    public function getVacancesConfigId(): int { return $this->vacancesConfigId; }
    public function setVacancesConfigId(int $id): static { $this->vacancesConfigId = $id; return $this; }

    public function getNumInter(): int { return $this->numInter; }
    public function setNumInter(int $n): static { $this->numInter = $n; return $this; }

    public function getToken(): string { return $this->token; }
    public function setToken(string $t): static { $this->token = $t; return $this; }

    public function getDateDebutConge(): ?\DateTimeImmutable { return $this->dateDebutConge; }
    public function setDateDebutConge(?\DateTimeImmutable $d): static { $this->dateDebutConge = $d; return $this; }

    public function getDateFinConge(): ?\DateTimeImmutable { return $this->dateFinConge; }
    public function setDateFinConge(?\DateTimeImmutable $d): static { $this->dateFinConge = $d; return $this; }

    public function getDisponibleRemplacement(): ?bool { return $this->disponibleRemplacement; }
    public function setDisponibleRemplacement(?bool $b): static { $this->disponibleRemplacement = $b; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $c): static { $this->commentaire = $c; return $this; }

    public function isRepondu(): bool { return $this->repondu; }
    public function setRepondu(bool $b): static { $this->repondu = $b; return $this; }

    public function getReponduLe(): ?\DateTimeImmutable { return $this->reponduLe; }
    public function setReponduLe(?\DateTimeImmutable $d): static { $this->reponduLe = $d; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
