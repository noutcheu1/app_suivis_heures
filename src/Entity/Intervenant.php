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

    #[ORM\Column(name: 'exp_bb_moins_1an', nullable: true)]
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

    #[ORM\Column(length: 40, nullable: true, options: ['default' => '0'])]
    private ?string $mutuelle = '0';

    #[ORM\Column(options: ['default' => false])]
    private ?bool $cmu = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $disponibilites = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(length: 150, nullable: true, options: ['default' => 'En attente'])]
    private ?string $candidatureRetenue = 'En attente';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEntretien = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $travailVoulu = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $nomJeuneFille = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column]
    private ?\DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the legacy numInter from the old "intervenants" table,
     * extracted from num_salarie (format "CAND_XXXXXX"). This value maps to
     * horaireinter.numInter and relevemensuelinter.numInter in imported data.
     */
    public function getLegacyNumInter(): ?int
    {
        if ($this->numSalarie !== null && preg_match('/^CAND_0*(\d+)$/', $this->numSalarie, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    public function getNomCompletInter(): string
    {
        return trim(($this->nom ?? '') . ' ' . ($this->prenom ?? ''));
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

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieuNaissance;
    }

    public function setLieuNaissance(?string $lieuNaissance): static
    {
        $this->lieuNaissance = $lieuNaissance;
        return $this;
    }

    public function getPaysNaissance(): ?string
    {
        return $this->paysNaissance;
    }

    public function setPaysNaissance(?string $paysNaissance): static
    {
        $this->paysNaissance = $paysNaissance;
        return $this;
    }

    public function getNationalite(): ?string
    {
        return $this->nationalite;
    }

    public function setNationalite(?string $nationalite): static
    {
        $this->nationalite = $nationalite;
        return $this;
    }

    public function getNumTitreSejour(): ?string
    {
        return $this->numTitreSejour;
    }

    public function setNumTitreSejour(?string $numTitreSejour): static
    {
        $this->numTitreSejour = $numTitreSejour;
        return $this;
    }

    public function getDateTitreSejour(): ?\DateTimeInterface
    {
        return $this->dateTitreSejour;
    }

    public function setDateTitreSejour(?\DateTimeInterface $dateTitreSejour): static
    {
        $this->dateTitreSejour = $dateTitreSejour;
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

    public function getSecteur(): ?string
    {
        return $this->secteur;
    }

    public function setSecteur(?string $secteur): static
    {
        $this->secteur = $secteur;
        return $this;
    }

    public function getQuartier(): ?string
    {
        return $this->quartier;
    }

    public function setQuartier(?string $quartier): static
    {
        $this->quartier = $quartier;
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

    public function getTelFixe(): ?string
    {
        return $this->telFixe;
    }

    public function setTelFixe(?string $telFixe): static
    {
        $this->telFixe = $telFixe;
        return $this;
    }

    public function getTelUrgence(): ?string
    {
        return $this->telUrgence;
    }

    public function setTelUrgence(?string $telUrgence): static
    {
        $this->telUrgence = $telUrgence;
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

    public function getStatutHandicap(): ?bool
    {
        return $this->statutHandicap;
    }

    public function setStatutHandicap(?bool $statutHandicap): static
    {
        $this->statutHandicap = $statutHandicap;
        return $this;
    }

    public function getPermis(): ?bool
    {
        return $this->permis;
    }

    public function setPermis(?bool $permis): static
    {
        $this->permis = $permis;
        return $this;
    }

    public function getVehicule(): ?bool
    {
        return $this->vehicule;
    }

    public function setVehicule(?bool $vehicule): static
    {
        $this->vehicule = $vehicule;
        return $this;
    }

    public function getStatutPro(): ?string
    {
        return $this->statutPro;
    }

    public function setStatutPro(?string $statutPro): static
    {
        $this->statutPro = $statutPro;
        return $this;
    }

    public function getSituationFamiliale(): ?string
    {
        return $this->situationFamiliale;
    }

    public function setSituationFamiliale(?string $situationFamiliale): static
    {
        $this->situationFamiliale = $situationFamiliale;
        return $this;
    }

    public function getDiplomes(): ?string
    {
        return $this->diplomes;
    }

    public function setDiplomes(?string $diplomes): static
    {
        $this->diplomes = $diplomes;
        return $this;
    }

    public function getQualifications(): ?string
    {
        return $this->qualifications;
    }

    public function setQualifications(?string $qualifications): static
    {
        $this->qualifications = $qualifications;
        return $this;
    }

    public function getExpBbMoins1an(): ?bool
    {
        return $this->expBbMoins1an;
    }

    public function setExpBbMoins1an(?bool $expBbMoins1an): static
    {
        $this->expBbMoins1an = $expBbMoins1an;
        return $this;
    }

    public function getEnfantHandicape(): ?bool
    {
        return $this->enfantHandicape;
    }

    public function setEnfantHandicape(?bool $enfantHandicape): static
    {
        $this->enfantHandicape = $enfantHandicape;
        return $this;
    }

    public function getDateEntree(): ?\DateTimeInterface
    {
        return $this->dateEntree;
    }

    public function setDateEntree(?\DateTimeInterface $dateEntree): static
    {
        $this->dateEntree = $dateEntree;
        return $this;
    }

    public function getDateSortie(): ?\DateTimeInterface
    {
        return $this->dateSortie;
    }

    public function setDateSortie(?\DateTimeInterface $dateSortie): static
    {
        $this->dateSortie = $dateSortie;
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

    public function getCertification(): ?string
    {
        return $this->certification;
    }

    public function setCertification(?string $certification): static
    {
        $this->certification = $certification;
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

    public function getRechercheComplement(): ?bool
    {
        return $this->rechercheComplement;
    }

    public function setRechercheComplement(?bool $rechercheComplement): static
    {
        $this->rechercheComplement = $rechercheComplement;
        return $this;
    }

    public function getNbHeuresSemaine(): ?string
    {
        return $this->nbHeuresSemaine;
    }

    public function setNbHeuresSemaine(?string $nbHeuresSemaine): static
    {
        $this->nbHeuresSemaine = $nbHeuresSemaine;
        return $this;
    }

    public function getNbHeuresMois(): ?string
    {
        return $this->nbHeuresMois;
    }

    public function setNbHeuresMois(?string $nbHeuresMois): static
    {
        $this->nbHeuresMois = $nbHeuresMois;
        return $this;
    }

    public function getProposerPsc1(): ?bool
    {
        return $this->proposerPsc1;
    }

    public function setProposerPsc1(?bool $proposerPsc1): static
    {
        $this->proposerPsc1 = $proposerPsc1;
        return $this;
    }

    public function getJustificatifs(): ?string
    {
        return $this->justificatifs;
    }

    public function setJustificatifs(?string $justificatifs): static
    {
        $this->justificatifs = $justificatifs;
        return $this;
    }

    public function getDateModification(): ?string
    {
        return $this->dateModification;
    }

    public function setDateModification(?string $dateModification): static
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    public function getSuivi(): ?string
    {
        return $this->suivi;
    }

    public function setSuivi(?string $suivi): static
    {
        $this->suivi = $suivi;
        return $this;
    }

    public function getArretTravail(): ?bool
    {
        return $this->arretTravail;
    }

    public function setArretTravail(?bool $arretTravail): static
    {
        $this->arretTravail = $arretTravail;
        return $this;
    }

    public function getDateFinArret(): ?\DateTimeInterface
    {
        return $this->dateFinArret;
    }

    public function setDateFinArret(?\DateTimeInterface $dateFinArret): static
    {
        $this->dateFinArret = $dateFinArret;
        return $this;
    }

    public function getArchiveTemporaire(): ?bool
    {
        return $this->archiveTemporaire;
    }

    public function setArchiveTemporaire(?bool $archiveTemporaire): static
    {
        $this->archiveTemporaire = $archiveTemporaire;
        return $this;
    }

    public function getDateDebutArchiveTemporaire(): ?\DateTimeInterface
    {
        return $this->dateDebutArchiveTemporaire;
    }

    public function setDateDebutArchiveTemporaire(?\DateTimeInterface $dateDebutArchiveTemporaire): static
    {
        $this->dateDebutArchiveTemporaire = $dateDebutArchiveTemporaire;
        return $this;
    }

    public function getDateFinArchiveTemporaire(): ?\DateTimeInterface
    {
        return $this->dateFinArchiveTemporaire;
    }

    public function setDateFinArchiveTemporaire(?\DateTimeInterface $dateFinArchiveTemporaire): static
    {
        $this->dateFinArchiveTemporaire = $dateFinArchiveTemporaire;
        return $this;
    }

    public function getRepassage(): ?bool
    {
        return $this->repassage;
    }

    public function setRepassage(?bool $repassage): static
    {
        $this->repassage = $repassage;
        return $this;
    }

    public function getMutuelle(): ?string
    {
        return $this->mutuelle;
    }

    public function setMutuelle(?string $mutuelle): static
    {
        $this->mutuelle = $mutuelle;
        return $this;
    }

    public function getCmu(): ?bool
    {
        return $this->cmu;
    }

    public function setCmu(?bool $cmu): static
    {
        $this->cmu = $cmu;
        return $this;
    }

    public function getDisponibilites(): ?string
    {
        return $this->disponibilites;
    }

    public function setDisponibilites(?string $disponibilites): static
    {
        $this->disponibilites = $disponibilites;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;
        return $this;
    }

    public function getCandidatureRetenue(): ?string
    {
        return $this->candidatureRetenue;
    }

    public function setCandidatureRetenue(?string $candidatureRetenue): static
    {
        $this->candidatureRetenue = $candidatureRetenue;
        return $this;
    }

    public function getDateEntretien(): ?\DateTimeInterface
    {
        return $this->dateEntretien;
    }

    public function setDateEntretien(?\DateTimeInterface $dateEntretien): static
    {
        $this->dateEntretien = $dateEntretien;
        return $this;
    }

    public function getTravailVoulu(): ?string
    {
        return $this->travailVoulu;
    }

    public function setTravailVoulu(?string $travailVoulu): static
    {
        $this->travailVoulu = $travailVoulu;
        return $this;
    }

    public function getNomJeuneFille(): ?string
    {
        return $this->nomJeuneFille;
    }

    public function setNomJeuneFille(?string $nomJeuneFille): static
    {
        $this->nomJeuneFille = $nomJeuneFille;
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

    public function __toString(): string
    {
        return $this->getNomCompletInter();
    }
}
