<?php

namespace App\Entity\Principal;

use App\Repository\EnfantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnfantRepository::class)]
#[ORM\Table(name: 'enfants')]
class Enfant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idEnfant_Enfants')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_Enfants', length: 50)]
    private ?string $nom = null;

    #[ORM\Column(name: 'prenom_Enfants', length: 50, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(name: 'dateNaiss_Enfants', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaiss = null;

    #[ORM\Column(name: 'numero_Famille', length: 10)]
    private ?string $numeroFamille = null;

    #[ORM\Column(name: 'concernGarde_Enfants', nullable: true)]
    private ?bool $concernGarde = null;

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getDateNaiss(): ?\DateTimeInterface { return $this->dateNaiss; }
    public function setDateNaiss(?\DateTimeInterface $dateNaiss): static { $this->dateNaiss = $dateNaiss; return $this; }

    public function getNumeroFamille(): ?string { return $this->numeroFamille; }
    public function setNumeroFamille(string $numeroFamille): static { $this->numeroFamille = $numeroFamille; return $this; }

    public function getConcernGarde(): ?bool { return $this->concernGarde; }
    public function setConcernGarde(?bool $concernGarde): static { $this->concernGarde = $concernGarde; return $this; }

    public function getNomComplet(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }

    public function getAgeEnMois(\DateTimeInterface $refDate): int
    {
        if (!$this->dateNaiss) return 0;
        $diff = $this->dateNaiss->diff($refDate);
        return $diff->y * 12 + $diff->m;
    }

    public function getTranche(\DateTimeInterface $refDate): string
    {
        $mois = $this->getAgeEnMois($refDate);
        if ($mois < 36)  return 'moins_3ans';
        if ($mois < 72)  return '3_6ans';
        return 'plus_6ans';
    }
}
