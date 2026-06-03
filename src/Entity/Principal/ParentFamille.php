<?php

namespace App\Entity\Principal;

use App\Repository\ParentFamilleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParentFamilleRepository::class)]
#[ORM\Table(name: 'parents')]
class ParentFamille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idParent_Parents')]
    private ?int $id = null;

    #[ORM\Column(name: 'titre_Parents', length: 3, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(name: 'nom_Parents', length: 50, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(name: 'prenom_Parents', length: 50, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(name: 'telTravail_Parents', length: 14, nullable: true)]
    private ?string $telTravail = null;

    #[ORM\Column(name: 'telPortable_Parents', length: 14, nullable: true)]
    private ?string $telPortable = null;

    #[ORM\Column(name: 'email_Parents', length: 60, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'profession_Parents', length: 25, nullable: true)]
    private ?string $profession = null;

    #[ORM\Column(name: 'numero_Famille', length: 10, nullable: true)]
    private ?string $numeroFamille = null;

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function getNom(): ?string { return $this->nom; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function getTelTravail(): ?string { return $this->telTravail; }
    public function getTelPortable(): ?string { return $this->telPortable; }
    public function getEmail(): ?string { return $this->email; }
    public function getProfession(): ?string { return $this->profession; }
    public function getNumeroFamille(): ?string { return $this->numeroFamille; }

    public function getNomComplet(): string
    {
        return trim(($this->titre ? $this->titre . ' ' : '') . $this->prenom . ' ' . $this->nom);
    }
}
