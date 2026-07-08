<?php

namespace App\Entity\Horaire;

use App\Repository\VacancesReponseFamilleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Réponse d'UNE famille à une campagne de congés (VacancesConfig).
 * Créée (vide, avec un jeton) à l'envoi des emails, puis remplie via le lien.
 */
#[ORM\Entity(repositoryClass: VacancesReponseFamilleRepository::class)]
#[ORM\Table(name: 'vacances_reponse_famille')]
#[ORM\UniqueConstraint(name: 'uniq_vac_fam', columns: ['vacancesConfigId', 'numFam'])]
class VacancesReponseFamille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Campagne concernée (id de VacancesConfig). */
    #[ORM\Column]
    private int $vacancesConfigId = 0;

    /** Numéro de la famille (PM / PGE). */
    #[ORM\Column(length: 20)]
    private string $numFam = '';

    /** Jeton unique du lien (magic link) → identifie la réponse sans connexion. */
    #[ORM\Column(length: 64, unique: true)]
    private string $token = '';

    /** Situation choisie par la famille : 'aucune' | 'absence' | 'modification'. */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $situation = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateDebutAbsence = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateFinAbsence = null;

    /** (Déprécié — remplacé par situation) La famille maintient-elle les prestations ? */
    #[ORM\Column(nullable: true)]
    private ?bool $maintienPrestation = null;

    /**
     * En cas d'absence de son intervenant habituel, la famille souhaite-t-elle
     * un remplacement (true) ou préfère-t-elle suspendre la prestation (false) ?
     * null = non renseigné (ou famille totalement absente → sans objet).
     */
    #[ORM\Column(nullable: true)]
    private ?bool $souhaiteRemplacement = null;

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

    public function getNumFam(): string { return $this->numFam; }
    public function setNumFam(string $n): static { $this->numFam = $n; return $this; }

    public function getToken(): string { return $this->token; }
    public function setToken(string $t): static { $this->token = $t; return $this; }

    public function getSituation(): ?string { return $this->situation; }
    public function setSituation(?string $s): static { $this->situation = $s; return $this; }

    public function getDateDebutAbsence(): ?\DateTimeImmutable { return $this->dateDebutAbsence; }
    public function setDateDebutAbsence(?\DateTimeImmutable $d): static { $this->dateDebutAbsence = $d; return $this; }

    public function getDateFinAbsence(): ?\DateTimeImmutable { return $this->dateFinAbsence; }
    public function setDateFinAbsence(?\DateTimeImmutable $d): static { $this->dateFinAbsence = $d; return $this; }

    public function getMaintienPrestation(): ?bool { return $this->maintienPrestation; }
    public function setMaintienPrestation(?bool $b): static { $this->maintienPrestation = $b; return $this; }

    public function getSouhaiteRemplacement(): ?bool { return $this->souhaiteRemplacement; }
    public function setSouhaiteRemplacement(?bool $b): static { $this->souhaiteRemplacement = $b; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $c): static { $this->commentaire = $c; return $this; }

    public function isRepondu(): bool { return $this->repondu; }
    public function setRepondu(bool $b): static { $this->repondu = $b; return $this; }

    public function getReponduLe(): ?\DateTimeImmutable { return $this->reponduLe; }
    public function setReponduLe(?\DateTimeImmutable $d): static { $this->reponduLe = $d; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
