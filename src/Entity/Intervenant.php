<?php

namespace App\Entity;

use App\Repository\IntervenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IntervenantRepository::class)]
#[ORM\Table(name: 'intervenants_unifie')]
class Intervenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $numSalarie = null;

    #[ORM\Column(length: 21, nullable: true)]
    private ?string $numSs = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $lieuNaissance = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $paysNaissance = null;

    #[ORM\Column(length: 25, nullable: true)]
    private ?string $nationalite = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $numTitreSejour = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateTitreSejour = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $secteur = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $quartier = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $telPortable = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $telFixe = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $telUrgence = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $statutHandicap = false;

    #[ORM\Column(nullable: true)]
    private ?bool $permis = null;

    #[ORM\Column(nullable: true)]
    private ?bool $vehicule = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $statutPro = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $situationFamiliale = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $diplomes = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $qualifications = null;

    #[ORM\Column(nullable: true)]
    private ?bool $expBbMoins1an = null;

    #[ORM\Column(nullable: true)]
    private ?bool $enfantHandicape = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEntree = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSortie = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $archive = false;

    #[ORM\Column(length: 240, nullable: true)]
    private ?string $certification = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $tauxHoraire = null;

    #[ORM\Column(nullable: true)]
    private ?bool $rechercheComplement = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nbHeuresSemaine = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nbHeuresMois = null;

    #[ORM\Column(nullable: true)]
    private ?bool $proposerPsc1 = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $justificatifs = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $dateModification = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $suivi = null;

    #[ORM\Column(nullable: true)]
    private ?bool $arretTravail = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinArret = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $archiveTemporaire = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebutArchiveTemporaire = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinArchiveTemporaire = null;

    #[ORM\Column(nullable: true)]
    private ?bool $repassage = null;

    #[ORM\Column(length: 40, options: ['default' => '0'])]
    private ?string $mutuelle = '0';

    #[ORM\Column(options: ['default' => false])]
    private ?bool $cmu = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $disponibilites = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(length: 150, options: ['default' => 'En attente'])]
    private ?string $candidatureRetenue = 'En attente';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $dateEntretien = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $travailVoulu = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nomJeuneFille = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumSalarie(): ?string
    {
        return $this->numSalarie;
    }

    public function setNumSalarie(?string $numSalarie): static
    {
        $this->numSalarie = $numSalarie;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

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

    // Getters et setters supplémentaires pour les propriétés importantes
    public function getNumSs(): ?string
    {
        return $this->numSs;
    }

    public function setNumSs(?string $numSs): static
    {
        $this->numSs = $numSs;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

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

    public function getTelPortable(): ?string
    {
        return $this->telPortable;
    }

    public function setTelPortable(?string $telPortable): static
    {
        $this->telPortable = $telPortable;

        return $this;
    }

    public function getTauxHoraire(): ?string
    {
        return $this->tauxHoraire;
    }

    public function setTauxHoraire(?string $tauxHoraire): static
    {
        $this->tauxHoraire = $tauxHoraire;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom . ' ' . $this->prenom;
    }
}
