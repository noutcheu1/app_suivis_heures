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
    private ?\DateTime $createdAt = null;

    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

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

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getSecteur(): ?string { return $this->secteur; }
    public function setSecteur(?string $secteur): static { $this->secteur = $secteur; return $this; }

    public function getQuartier(): ?string { return $this->quartier; }
    public function setQuartier(?string $quartier): static { $this->quartier = $quartier; return $this; }

    public function getNumAlloc(): ?string { return $this->numAlloc; }
    public function setNumAlloc(?string $numAlloc): static { $this->numAlloc = $numAlloc; return $this; }

    public function getNumUrssaf(): ?string { return $this->numUrssaf; }
    public function setNumUrssaf(?string $numUrssaf): static { $this->numUrssaf = $numUrssaf; return $this; }

    public function getDateEntree(): ?\DateTimeInterface { return $this->dateEntree; }
    public function setDateEntree(?\DateTimeInterface $dateEntree): static { $this->dateEntree = $dateEntree; return $this; }

    public function getDateSortie(): ?\DateTimeInterface { return $this->dateSortie; }
    public function setDateSortie(?\DateTimeInterface $dateSortie): static { $this->dateSortie = $dateSortie; return $this; }

    public function getTypeLogement(): ?string { return $this->typeLogement; }
    public function setTypeLogement(?string $typeLogement): static { $this->typeLogement = $typeLogement; return $this; }

    public function getSuperficie(): ?int { return $this->superficie; }
    public function setSuperficie(?int $superficie): static { $this->superficie = $superficie; return $this; }

    public function getNbEtage(): ?int { return $this->nbEtage; }
    public function setNbEtage(?int $nbEtage): static { $this->nbEtage = $nbEtage; return $this; }

    public function getNbChambres(): ?int { return $this->nbChambres; }
    public function setNbChambres(?int $nbChambres): static { $this->nbChambres = $nbChambres; return $this; }

    public function getNbSdb(): ?int { return $this->nbSdb; }
    public function setNbSdb(?int $nbSdb): static { $this->nbSdb = $nbSdb; return $this; }

    public function getNbSanitaire(): ?int { return $this->nbSanitaire; }
    public function setNbSanitaire(?int $nbSanitaire): static { $this->nbSanitaire = $nbSanitaire; return $this; }

    public function getArretBus(): ?string { return $this->arretBus; }
    public function setArretBus(?string $arretBus): static { $this->arretBus = $arretBus; return $this; }

    public function getNumBus(): ?string { return $this->numBus; }
    public function setNumBus(?string $numBus): static { $this->numBus = $numBus; return $this; }

    public function getVehicule(): ?bool { return $this->vehicule; }
    public function setVehicule(?bool $vehicule): static { $this->vehicule = $vehicule; return $this; }

    public function getGardePartielle(): ?bool { return $this->gardePartielle; }
    public function setGardePartielle(?bool $gardePartielle): static { $this->gardePartielle = $gardePartielle; return $this; }

    public function getRepassage(): ?bool { return $this->repassage; }
    public function setRepassage(?bool $repassage): static { $this->repassage = $repassage; return $this; }

    public function getPrestMenage(): ?bool { return $this->prestMenage; }
    public function setPrestMenage(?bool $prestMenage): static { $this->prestMenage = $prestMenage; return $this; }

    public function getPrestGardeEnfants(): ?bool { return $this->prestGardeEnfants; }
    public function setPrestGardeEnfants(?bool $prestGardeEnfants): static { $this->prestGardeEnfants = $prestGardeEnfants; return $this; }

    public function getNbSemVacancesMenage(): ?int { return $this->nbSemVacancesMenage; }
    public function setNbSemVacancesMenage(?int $nbSemVacancesMenage): static { $this->nbSemVacancesMenage = $nbSemVacancesMenage; return $this; }

    public function getNbSemVacancesGe(): ?int { return $this->nbSemVacancesGe; }
    public function setNbSemVacancesGe(?int $nbSemVacancesGe): static { $this->nbSemVacancesGe = $nbSemVacancesGe; return $this; }

    public function getOptionsFamille(): ?string { return $this->optionsFamille; }
    public function setOptionsFamille(?string $optionsFamille): static { $this->optionsFamille = $optionsFamille; return $this; }

    public function getModePaiement(): ?string { return $this->modePaiement; }
    public function setModePaiement(?string $modePaiement): static { $this->modePaiement = $modePaiement; return $this; }

    public function getMandataire(): ?bool { return $this->mandataire; }
    public function setMandataire(?bool $mandataire): static { $this->mandataire = $mandataire; return $this; }

    public function getObservationsFamille(): ?string { return $this->observationsFamille; }
    public function setObservationsFamille(?string $observationsFamille): static { $this->observationsFamille = $observationsFamille; return $this; }

    public function getRemarquesFamille(): ?string { return $this->remarquesFamille; }
    public function setRemarquesFamille(?string $remarquesFamille): static { $this->remarquesFamille = $remarquesFamille; return $this; }

    public function getEnfantHandicape(): ?bool { return $this->enfantHandicape; }
    public function setEnfantHandicape(?bool $enfantHandicape): static { $this->enfantHandicape = $enfantHandicape; return $this; }

    public function __toString(): string
    {
        return $this->nomFamille ?? $this->numeroFamille;
    }
}
