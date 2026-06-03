<?php

namespace App\Entity\Horaire;

use App\Repository\RelevemensuelfamRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RelevemensuelfamRepository::class)]
#[ORM\Table(name: 'relevemensuelfam')]
class Relevemensuelfam
{
    #[ORM\Id]
    #[ORM\Column(name: 'numFam', length: 10)]
    private ?string $numFam = null;

    #[ORM\Id]
    #[ORM\Column(length: 7)]
    private ?string $moisannee = null;

    #[ORM\Id]
    #[ORM\Column(name: 'typePresta', length: 4)]
    private ?string $typePresta = null;

    #[ORM\Column(name: 'typeRèglement', length: 15, nullable: true)]
    private ?string $typeReglement = null;

    #[ORM\Column(name: 'numChèque', length: 15, nullable: true)]
    private ?string $numCheque = null;

    #[ORM\Column(name: 'nbrCESU', nullable: true)]
    private ?int $nbrCESU = null;

    #[ORM\Column(name: 'montantPrincipal', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $montantPrincipal = null;

    #[ORM\Column(name: 'complementCESU', length: 15, nullable: true)]
    private ?string $complementCESU = null;

    #[ORM\Column(name: 'montantComplement', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $montantComplement = null;

    #[ORM\Column(name: 'libelerSupl', type: Types::TEXT, nullable: true)]
    private ?string $libelerSupl = null;

    #[ORM\Column(name: 'montantSupl', type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $montantSupl = null;

    #[ORM\Column(name: 'avisPonctualite', nullable: true)]
    private ?int $avisPonctualite = null;

    #[ORM\Column(name: 'avisReguRela', nullable: true)]
    private ?int $avisReguRela = null;

    #[ORM\Column(name: 'avisRespectHo', nullable: true)]
    private ?int $avisRespectHo = null;

    #[ORM\Column(name: 'avisQualiteTr', nullable: true)]
    private ?int $avisQualiteTr = null;

    #[ORM\Column(name: 'signerLe', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $signerLe = null;

    public function getNumFam(): ?string { return $this->numFam; }
    public function setNumFam(string $numFam): static { $this->numFam = $numFam; return $this; }

    public function getMoisannee(): ?string { return $this->moisannee; }
    public function setMoisannee(string $moisannee): static { $this->moisannee = $moisannee; return $this; }

    public function getTypePresta(): ?string { return $this->typePresta; }
    public function setTypePresta(string $typePresta): static { $this->typePresta = $typePresta; return $this; }

    public function getTypeReglement(): ?string { return $this->typeReglement; }
    public function setTypeReglement(?string $typeReglement): static { $this->typeReglement = $typeReglement; return $this; }

    public function getNumCheque(): ?string { return $this->numCheque; }
    public function setNumCheque(?string $numCheque): static { $this->numCheque = $numCheque; return $this; }

    public function getNbrCESU(): ?int { return $this->nbrCESU; }
    public function setNbrCESU(?int $nbrCESU): static { $this->nbrCESU = $nbrCESU; return $this; }

    public function getMontantPrincipal(): ?string { return $this->montantPrincipal; }
    public function setMontantPrincipal(?string $montantPrincipal): static { $this->montantPrincipal = $montantPrincipal; return $this; }

    public function getComplementCESU(): ?string { return $this->complementCESU; }
    public function setComplementCESU(?string $complementCESU): static { $this->complementCESU = $complementCESU; return $this; }

    public function getMontantComplement(): ?string { return $this->montantComplement; }
    public function setMontantComplement(?string $montantComplement): static { $this->montantComplement = $montantComplement; return $this; }

    public function getLibelerSupl(): ?string { return $this->libelerSupl; }
    public function setLibelerSupl(?string $libelerSupl): static { $this->libelerSupl = $libelerSupl; return $this; }

    public function getMontantSupl(): ?string { return $this->montantSupl; }
    public function setMontantSupl(?string $montantSupl): static { $this->montantSupl = $montantSupl; return $this; }

    public function getAvisPonctualite(): ?int { return $this->avisPonctualite; }
    public function setAvisPonctualite(?int $avisPonctualite): static { $this->avisPonctualite = $avisPonctualite; return $this; }

    public function getAvisReguRela(): ?int { return $this->avisReguRela; }
    public function setAvisReguRela(?int $avisReguRela): static { $this->avisReguRela = $avisReguRela; return $this; }

    public function getAvisRespectHo(): ?int { return $this->avisRespectHo; }
    public function setAvisRespectHo(?int $avisRespectHo): static { $this->avisRespectHo = $avisRespectHo; return $this; }

    public function getAvisQualiteTr(): ?int { return $this->avisQualiteTr; }
    public function setAvisQualiteTr(?int $avisQualiteTr): static { $this->avisQualiteTr = $avisQualiteTr; return $this; }

    public function getSignerLe(): ?\DateTimeInterface { return $this->signerLe; }
    public function setSignerLe(?\DateTimeInterface $signerLe): static { $this->signerLe = $signerLe; return $this; }

    public function __toString(): string
    {
        return sprintf('Relevé %s - Famille %s (%s)', $this->moisannee, $this->numFam, $this->typePresta);
    }
}
