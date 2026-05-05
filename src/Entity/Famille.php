<?php

namespace App\Entity;

use App\Repository\FamilleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FamilleRepository::class)]
#[ORM\Table(name: 'famille_normalisee')]
class Famille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private ?string $numeroFamille = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nomFamille = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $secteur = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $quartier = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $telDom = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $numAlloc = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $numUrssaf = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEntree = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSortie = null;

    #[ORM\Column(nullable: true)]
    private ?bool $archive = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $typeLogement = null;

    #[ORM\Column(nullable: true)]
    private ?int $superficie = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbEtage = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbChambres = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbSdb = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbSanitaire = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $arretBus = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $numBus = null;

    #[ORM\Column(nullable: true)]
    private ?bool $vehicule = null;

    #[ORM\Column(nullable: true)]
    private ?bool $gardePartielle = null;

    #[ORM\Column(nullable: true)]
    private ?bool $repassage = null;

    #[ORM\Column(nullable: true)]
    private ?bool $prestMenage = null;

    #[ORM\Column(nullable: true)]
    private ?bool $prestGardeEnfants = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbSemVacancesMenage = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbSemVacancesGe = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $optionsFamille = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $modePaiement = null;

    #[ORM\Column(nullable: true)]
    private ?bool $mandataire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observationsFamille = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $remarquesFamille = null;

    #[ORM\Column(nullable: true)]
    private ?bool $enfantHandicape = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroFamille(): ?string
    {
        return $this->numeroFamille;
    }

    public function setNumeroFamille(string $numeroFamille): static
    {
        $this->numeroFamille = $numeroFamille;

        return $this;
    }

    public function getNomFamille(): ?string
    {
        return $this->nomFamille;
    }

    public function setNomFamille(?string $nomFamille): static
    {
        $this->nomFamille = $nomFamille;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): static
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getTelDom(): ?string
    {
        return $this->telDom;
    }

    public function setTelDom(?string $telDom): static
    {
        $this->telDom = $telDom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getArchive(): ?bool
    {
        return $this->archive;
    }

    public function setArchive(?bool $archive): static
    {
        $this->archive = $archive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nomFamille ?? $this->numeroFamille;
    }
}
