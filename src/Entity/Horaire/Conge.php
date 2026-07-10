<?php

namespace App\Entity\Horaire;

use App\Repository\CongeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Congé / absence — DONNÉE MÉTIER PERMANENTE, indépendante des campagnes.
 *
 * Source de vérité des absences pour le planning, les remplacements et les
 * campagnes. Une personne (famille ou intervenant) peut avoir PLUSIEURS congés,
 * déclarés à tout moment (origine LIBRE) ou collectés via une campagne (origine
 * CAMPAGNE). Suppression douce via annuleLe (on garde l'historique).
 */
#[ORM\Entity(repositoryClass: CongeRepository::class)]
#[ORM\Table(name: 'conge')]
#[ORM\Index(name: 'idx_conge_personne', columns: ['typePersonne', 'personneId'])]
class Conge
{
    public const PERSONNE_FAMILLE     = 'FAMILLE';
    public const PERSONNE_INTERVENANT = 'INTERVENANT';

    public const ORIGINE_LIBRE    = 'LIBRE';
    public const ORIGINE_CAMPAGNE = 'CAMPAGNE';

    /** Statut de validation. Les familles sont VALIDE d'office ; les intervenants passent par EN_ATTENTE. */
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_VALIDE     = 'VALIDE';
    public const STATUT_REFUSE     = 'REFUSE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** FAMILLE | INTERVENANT. */
    #[ORM\Column(length: 12)]
    private string $typePersonne = self::PERSONNE_FAMILLE;

    /** Numéro famille (PM/PGE) ou numéro salarié intervenant, selon typePersonne. */
    #[ORM\Column(length: 20)]
    private string $personneId = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateFin = null;

    /** Motif / commentaire libre. */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motif = null;

    /** LIBRE (déclaré au fil de l'eau) | CAMPAGNE (collecté via une campagne). */
    #[ORM\Column(length: 12)]
    private string $origine = self::ORIGINE_LIBRE;

    /** Campagne d'origine si origine = CAMPAGNE. */
    #[ORM\Column(nullable: true)]
    private ?int $campagneId = null;

    /** EN_ATTENTE | VALIDE | REFUSE (validation admin pour les intervenants). */
    #[ORM\Column(length: 12)]
    private string $statut = self::STATUT_VALIDE;

    // ── Attributs métier (nullable, requêtables par le matching) ──────────────

    /** Famille : maintient-elle les prestations pendant ce congé ? */
    #[ORM\Column(nullable: true)]
    private ?bool $maintienPrestation = null;

    /** Famille : souhaite-t-elle un remplacement si son intervenant est absent ? */
    #[ORM\Column(nullable: true)]
    private ?bool $souhaiteRemplacement = null;

    /** Intervenant : disponible pour des remplacements pendant ce congé ? */
    #[ORM\Column(nullable: true)]
    private ?bool $disponibleRemplacement = null;

    /** Suppression douce : congé annulé (on conserve la ligne pour l'historique). */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $annuleLe = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTypePersonne(): string { return $this->typePersonne; }
    public function setTypePersonne(string $v): static { $this->typePersonne = $v; return $this; }

    public function getPersonneId(): string { return $this->personneId; }
    public function setPersonneId(string $v): static { $this->personneId = $v; return $this; }

    public function getDateDebut(): ?\DateTimeImmutable { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeImmutable $d): static { $this->dateDebut = $d; return $this; }

    public function getDateFin(): ?\DateTimeImmutable { return $this->dateFin; }
    public function setDateFin(?\DateTimeImmutable $d): static { $this->dateFin = $d; return $this; }

    public function getMotif(): ?string { return $this->motif; }
    public function setMotif(?string $v): static { $this->motif = $v; return $this; }

    public function getOrigine(): string { return $this->origine; }
    public function setOrigine(string $v): static { $this->origine = $v; return $this; }

    public function getCampagneId(): ?int { return $this->campagneId; }
    public function setCampagneId(?int $v): static { $this->campagneId = $v; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $v): static { $this->statut = $v; return $this; }
    public function estValide(): bool { return $this->statut === self::STATUT_VALIDE; }
    public function estEnAttente(): bool { return $this->statut === self::STATUT_EN_ATTENTE; }
    public function estRefuse(): bool { return $this->statut === self::STATUT_REFUSE; }

    public function getMaintienPrestation(): ?bool { return $this->maintienPrestation; }
    public function setMaintienPrestation(?bool $v): static { $this->maintienPrestation = $v; return $this; }

    public function getSouhaiteRemplacement(): ?bool { return $this->souhaiteRemplacement; }
    public function setSouhaiteRemplacement(?bool $v): static { $this->souhaiteRemplacement = $v; return $this; }

    public function getDisponibleRemplacement(): ?bool { return $this->disponibleRemplacement; }
    public function setDisponibleRemplacement(?bool $v): static { $this->disponibleRemplacement = $v; return $this; }

    public function getAnnuleLe(): ?\DateTimeImmutable { return $this->annuleLe; }
    public function setAnnuleLe(?\DateTimeImmutable $d): static { $this->annuleLe = $d; return $this; }
    public function estAnnule(): bool { return $this->annuleLe !== null; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function toucher(): static { $this->updatedAt = new \DateTimeImmutable(); return $this; }
}
