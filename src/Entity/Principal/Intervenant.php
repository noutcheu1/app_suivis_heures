<?php

namespace App\Entity\Principal;

use App\Repository\IntervenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IntervenantRepository::class)]
#[ORM\Table(name: 'vue_intervenants')]
class Intervenant
{
    #[ORM\Id]
    #[ORM\Column(name: 'numSalarie_Intervenants')]
    private ?int $numSalarie_Intervenants = null;

    #[ORM\Column(name: 'idSalarie_Intervenants', length: 25, nullable: true)]
    private ?string $numSalarie = null;

    #[ORM\Column(name: 'numSS_Candidats', length: 21, nullable: true)]
    private ?string $numSs = null;

    #[ORM\Column(name: 'titre_Candidats', length: 3, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(name: 'nom_Candidats', length: 50)]
    private ?string $nom = null;

    #[ORM\Column(name: 'prenom_Candidats', length: 50)]
    private ?string $prenom = null;

    #[ORM\Column(name: 'dateNaiss_Candidats', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateNaissance = null;

    #[ORM\Column(name: 'lieuNaiss_Candidats', length: 20, nullable: true)]
    private ?string $lieuNaissance = null;

    #[ORM\Column(name: 'paysNaiss_Candidats', length: 20, nullable: true)]
    private ?string $paysNaissance = null;

    #[ORM\Column(name: 'nationalite_Candidats', length: 25, nullable: true)]
    private ?string $nationalite = null;

    #[ORM\Column(name: 'numTitreSejour', length: 15, nullable: true)]
    private ?string $numTitreSejour = null;

    private ?\DateTimeInterface $dateTitreSejour = null;

    #[ORM\Column(name: 'adresse_Candidats', length: 50, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(name: 'cp_Candidats', length: 5, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(name: 'ville_Candidats', length: 50, nullable: true)]
    private ?string $ville = null;

    private ?string $secteur = null;

    #[ORM\Column(name: 'Quartier_Candidats', length: 50, nullable: true)]
    private ?string $quartier = null;

    #[ORM\Column(name: 'telPortable_Candidats', length: 14, nullable: true)]
    private ?string $telPortable = null;

    #[ORM\Column(name: 'telFixe_Candidats', length: 14, nullable: true)]
    private ?string $telFixe = null;

    #[ORM\Column(name: 'TelUrg_Candidats', length: 14, nullable: true)]
    private ?string $telUrgence = null;

    #[ORM\Column(name: 'email_Candidats', length: 60, nullable: true)]
    private ?string $email = null;

    private ?bool $statutHandicap = false;

    #[ORM\Column(name: 'permis_Candidats', nullable: true)]
    private ?bool $permis = null;

    #[ORM\Column(name: 'vehicule_Candidats', nullable: true)]
    private ?bool $vehicule = null;

    #[ORM\Column(name: 'statutPro_Candidats', length: 15, nullable: true)]
    private ?string $statutPro = null;

    #[ORM\Column(name: 'situationFamiliale_Candidats', length: 15, nullable: true)]
    private ?string $situationFamiliale = null;

    #[ORM\Column(name: 'diplomes_Candidats', length: 150, nullable: true)]
    private ?string $diplomes = null;

    #[ORM\Column(name: 'qualifications_Candidats', length: 100, nullable: true)]
    private ?string $qualifications = null;

    #[ORM\Column(name: 'expBBmoins1a_Candidats', nullable: true)]
    private ?bool $expBbMoins1an = null;

    #[ORM\Column(name: 'enfantHand_Candidats', nullable: true)]
    private ?bool $enfantHandicape = null;

    #[ORM\Column(name: 'dateEntree_Intervenants', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEntree = null;

    #[ORM\Column(name: 'dateSortie_Intervenants', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateSortie = null;

    #[ORM\Column(name: 'archive_Intervenants', options: ['default' => false])]
    private ?bool $archive = false;

    #[ORM\Column(name: 'Certification_Intervenants', length: 240, nullable: true)]
    private ?string $certification = null;

    #[ORM\Column(name: 'tauxH_Intervenants', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $tauxHoraire = null;

    #[ORM\Column(name: 'rechCompl_Intervenants', nullable: true)]
    private ?bool $rechercheComplement = null;

    #[ORM\Column(name: 'nbHeureSem_Intervenants', length: 100, nullable: true)]
    private ?string $nbHeuresSemaine = null;

    #[ORM\Column(name: 'nbHeureMois_Intervenants', length: 100, nullable: true)]
    private ?string $nbHeuresMois = null;

    #[ORM\Column(name: 'ProposerPSC1_Intervenants', nullable: true)]
    private ?bool $proposerPsc1 = null;

    #[ORM\Column(name: 'justificatifs_Intervenants', length: 50, nullable: true)]
    private ?string $justificatifs = null;

    #[ORM\Column(name: 'dateModif_Intervenants', length: 100, nullable: true)]
    private ?string $dateModification = null;

    #[ORM\Column(name: 'suivi_Intervenants', length: 250, nullable: true)]
    private ?string $suivi = null;

    #[ORM\Column(name: 'arretTravail_Intervenants', nullable: true)]
    private ?bool $arretTravail = null;

    private ?\DateTimeInterface $dateFinArret = null;

    private ?bool $archiveTemporaire = false;

    private ?\DateTimeInterface $dateDebutArchiveTemporaire = null;

    private ?\DateTimeInterface $dateFinArchiveTemporaire = null;

    private ?bool $repassage = null;

    #[ORM\Column(name: 'Mutuelle_Candidats', length: 40, nullable: true, options: ['default' => '0'])]
    private ?string $mutuelle = '0';

    #[ORM\Column(name: 'CMU_Candidats', options: ['default' => false])]
    private ?bool $cmu = false;

    #[ORM\Column(name: 'disponibilites_Candidats', type: Types::TEXT, nullable: true)]
    private ?string $disponibilites = null;

    #[ORM\Column(name: 'observations_Candidats', type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(name: 'candidatureRetenue_Candidats', length: 150, nullable: true, options: ['default' => 'En attente'])]
    private ?string $candidatureRetenue = 'En attente';

    #[ORM\Column(name: 'dateEntretien_Candidats', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEntretien = null;

    #[ORM\Column(name: 'travailVoulu_Candidats', length: 50, nullable: true)]
    private ?string $travailVoulu = null;

    #[ORM\Column(name: 'nomJF_Candidats', length: 50, nullable: true)]
    private ?string $nomJeuneFille = null;

    private ?\DateTime $createdAt = null;

    private ?\DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->numSalarie_Intervenants;
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

    public function getDateNaissance(): ?\DateTimeImmutable
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeImmutable $dateNaissance): static
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

    /**
     * Returns true when the linked candidate's application has been accepted.
     * 'En attente' (the default) means the application is still pending.
     */
    public function isAccepted(): bool
    {
        $retenue = trim((string)$this->candidatureRetenue);
        return $retenue !== '' && strtolower($retenue) !== 'en attente';
    }

    public function __toString(): string
    {
        return $this->getNomCompletInter();
    }
}
