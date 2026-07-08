<?php

namespace App\Entity\Horaire;

use App\Repository\VacancesConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VacancesConfigRepository::class)]
#[ORM\Table(name: 'vacances_config')]
class VacancesConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Ex : "VACANCES D'ÉTÉ" */
    #[ORM\Column(length: 120)]
    private string $titre = '';

    /** Ex : "du 23 décembre au 5 janvier" */
    #[ORM\Column(length: 200, nullable: true)]
    private ?string $periodeTexte = null;

    /** Message ou consigne affiché sur le formulaire */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    /** Mois affichés dans le formulaire, séparés par virgules : "JUILLET,AOUT" */
    #[ORM\Column(length: 200, nullable: true)]
    private ?string $moisPeriodes = null;

    /** Date à partir de laquelle le formulaire apparaît sur les relevés */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateApparitionDebut = null;

    /** Date jusqu'à laquelle le formulaire apparaît sur les relevés */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateApparitionFin = null;

    #[ORM\Column]
    private bool $actif = false;

    // ── Campagne de congés (nouveau modèle) ──────────────────────────────────
    /** Période de vacances concernée : début. */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateDebut = null;

    /** Période de vacances concernée : fin. */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    /** Les personnes doivent répondre avant cette date. */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateLimiteReponse = null;

    /** brouillon | envoyee | cloturee */
    #[ORM\Column(length: 20, options: ['default' => 'brouillon'])]
    private string $statut = self::STATUT_BROUILLON;

    public const STATUT_BROUILLON = 'brouillon';
    public const STATUT_ENVOYEE   = 'envoyee';
    public const STATUT_CLOTUREE  = 'cloturee';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getDateDebut(): ?\DateTimeImmutable { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeImmutable $d): static { $this->dateDebut = $d; return $this; }

    public function getDateFin(): ?\DateTimeImmutable { return $this->dateFin; }
    public function setDateFin(?\DateTimeImmutable $d): static { $this->dateFin = $d; return $this; }

    public function getDateLimiteReponse(): ?\DateTimeImmutable { return $this->dateLimiteReponse; }
    public function setDateLimiteReponse(?\DateTimeImmutable $d): static { $this->dateLimiteReponse = $d; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getPeriodeTexte(): ?string { return $this->periodeTexte; }
    public function setPeriodeTexte(?string $periodeTexte): static { $this->periodeTexte = $periodeTexte; return $this; }

    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $message): static { $this->message = $message; return $this; }

    public function getMoisPeriodes(): ?string { return $this->moisPeriodes; }
    public function setMoisPeriodes(?string $moisPeriodes): static { $this->moisPeriodes = $moisPeriodes; return $this; }

    public function getDateApparitionDebut(): ?\DateTimeImmutable { return $this->dateApparitionDebut; }
    public function setDateApparitionDebut(?\DateTimeImmutable $date): static { $this->dateApparitionDebut = $date; return $this; }

    public function getDateApparitionFin(): ?\DateTimeImmutable { return $this->dateApparitionFin; }
    public function setDateApparitionFin(?\DateTimeImmutable $date): static { $this->dateApparitionFin = $date; return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
