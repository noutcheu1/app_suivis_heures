<?php

namespace App\Entity\Principal;

use App\Repository\ProposerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ligne de la table `proposer` — liaison Famille ↔ Intervenant.
 * PK composite : prestation + salarié + famille + typeAdh + jour + heureDebut.
 */
#[ORM\Entity(repositoryClass: ProposerRepository::class)]
#[ORM\Table(name: 'proposer')]
class Proposer
{
    #[ORM\Id]
    #[ORM\Column(name: 'idPresta_Prestations', length: 4)]
    private string $typePrestation;

    #[ORM\Id]
    #[ORM\Column(name: 'numSalarie_Intervenants', type: Types::INTEGER)]
    private int $numSalarie;

    #[ORM\Id]
    #[ORM\Column(name: 'numero_Famille', length: 10)]
    private string $numeroFamille;

    #[ORM\Id]
    #[ORM\Column(name: 'idADH_TypeADH', length: 5)]
    private string $typeAdh;

    #[ORM\Id]
    #[ORM\Column(name: 'jour_Proposer', length: 15)]
    private string $jour;

    #[ORM\Id]
    #[ORM\Column(name: 'hDeb_Proposer', type: Types::TIME_MUTABLE)]
    private \DateTimeInterface $heureDebut;

    #[ORM\Column(name: 'hFin_Proposer', type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(name: 'DateDeb_Proposer', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dateDeb;

    #[ORM\Column(name: 'dateFin_Proposer', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: 'Statut_Proposer', length: 20, nullable: true, options: ['default' => 'En attente'])]
    private ?string $statut = 'En attente';

    #[ORM\Column(name: 'modalites_Proposer', length: 200, nullable: true)]
    private ?string $modalites = null;

    #[ORM\Column(name: 'validInterv_Proposer', nullable: true)]
    private ?bool $validIntervenant = null;

    #[ORM\Column(name: 'validFamille_Proposer', nullable: true)]
    private ?bool $validFamille = null;

    #[ORM\Column(name: 'options_Proposer', length: 10, nullable: true)]
    private ?string $options = null;

    #[ORM\Column(name: 'dateModif_Proposer', length: 50, nullable: true)]
    private ?string $dateModif = null;

    #[ORM\Column(name: 'frequence_Proposer', nullable: true)]
    private ?int $frequence = null;

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getTypePrestation(): string { return $this->typePrestation; }
    public function getNumSalarie(): int        { return $this->numSalarie; }
    public function getNumeroFamille(): string  { return $this->numeroFamille; }
    public function getTypeAdh(): string        { return $this->typeAdh; }
    public function getJour(): string           { return $this->jour; }
    public function getHeureDebut(): \DateTimeInterface  { return $this->heureDebut; }
    public function getHeureFin(): ?\DateTimeInterface   { return $this->heureFin; }
    public function getDateDeb(): \DateTimeInterface     { return $this->dateDeb; }
    public function getDateFin(): ?\DateTimeInterface    { return $this->dateFin; }
    public function getStatut(): ?string        { return $this->statut; }
    public function getModalites(): ?string     { return $this->modalites; }
    public function getValidIntervenant(): ?bool { return $this->validIntervenant; }
    public function getValidFamille(): ?bool    { return $this->validFamille; }
    public function getOptions(): ?string       { return $this->options; }
    public function getDateModif(): ?string     { return $this->dateModif; }
    public function getFrequence(): ?int        { return $this->frequence; }

    // ── Setters ───────────────────────────────────────────────────────────────

    public function setTypePrestation(string $v): static { $this->typePrestation = $v; return $this; }
    public function setNumSalarie(int $v): static        { $this->numSalarie = $v; return $this; }
    public function setNumeroFamille(string $v): static  { $this->numeroFamille = $v; return $this; }
    public function setTypeAdh(string $v): static        { $this->typeAdh = $v; return $this; }
    public function setJour(string $v): static           { $this->jour = $v; return $this; }
    public function setHeureDebut(\DateTimeInterface $v): static  { $this->heureDebut = $v; return $this; }
    public function setHeureFin(?\DateTimeInterface $v): static   { $this->heureFin = $v; return $this; }
    public function setDateDeb(\DateTimeInterface $v): static     { $this->dateDeb = $v; return $this; }
    public function setDateFin(?\DateTimeInterface $v): static    { $this->dateFin = $v; return $this; }
    public function setStatut(?string $v): static        { $this->statut = $v; return $this; }
    public function setModalites(?string $v): static     { $this->modalites = $v; return $this; }
    public function setValidIntervenant(?bool $v): static { $this->validIntervenant = $v; return $this; }
    public function setValidFamille(?bool $v): static    { $this->validFamille = $v; return $this; }
    public function setOptions(?string $v): static       { $this->options = $v; return $this; }
    public function setDateModif(?string $v): static     { $this->dateModif = $v; return $this; }
    public function setFrequence(?int $v): static        { $this->frequence = $v; return $this; }

    // ── Helpers métier ────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        if ($this->dateFin === null) {
            return true;
        }
        return $this->dateFin >= new \DateTime('today');
    }

    public function getLabelPrestation(): string
    {
        return match ($this->typePrestation) {
            'ENFA' => 'Garde d\'enfants',
            'MENA' => 'Ménage',
            'BURO' => 'Bureau',
            'DRCT' => 'En direct',
            'DISP' => 'Pas disponible',
            default => $this->typePrestation,
        };
    }
}
