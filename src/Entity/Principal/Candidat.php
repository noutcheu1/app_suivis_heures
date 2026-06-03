<?php

namespace App\Entity\Principal;

use App\Repository\CandidatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
#[ORM\Table(name: 'candidats')]
class Candidat
{
    #[ORM\Id]
    #[ORM\Column(name: 'numcandidat_candidats', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'numSS_Candidats', length: 21, nullable: true)]
    private ?string $numSs = null;

    #[ORM\Column(name: 'nom_Candidats', length: 50, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(name: 'prenom_Candidats', length: 50, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(name: 'email_Candidats', length: 60, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'telPortable_Candidats', length: 14, nullable: true)]
    private ?string $telPortable = null;

    #[ORM\Column(name: 'titre_Candidats', length: 3, nullable: true)]
    private ?string $titre = null;

    public function getId(): ?int { return $this->id; }

    public function getNumSs(): ?string { return $this->numSs; }
    public function setNumSs(?string $numSs): static { $this->numSs = $numSs; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getTelPortable(): ?string { return $this->telPortable; }
    public function setTelPortable(?string $telPortable): static { $this->telPortable = $telPortable; return $this; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $titre): static { $this->titre = $titre; return $this; }

    public function getNomComplet(): string
    {
        return trim(($this->nom ?? '') . ' ' . ($this->prenom ?? ''));
    }
}
