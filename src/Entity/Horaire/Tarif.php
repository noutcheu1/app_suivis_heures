<?php

namespace App\Entity\Horaire;

use App\Repository\TarifRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TarifRepository::class)]
#[ORM\Table(name: 'tarifs_suivi')]
class Tarif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Règles tarifaires garde d'enfants — JSON : [["condition", valeur], ...] */
    #[ORM\Column(name: 'alheureGE', type: Types::TEXT)]
    private ?string $alheureGe = null;

    /** Règles tarifaires ménage — JSON : [["condition", valeur], ...] */
    #[ORM\Column(name: 'alheureM', type: Types::TEXT, nullable: true)]
    private ?string $alheureM = null;

    /** Règles de frais de gestion — JSON : [["condition1", "condition2", valeur], ...] */
    #[ORM\Column(name: 'fraisGestion', type: Types::TEXT)]
    private ?string $fraisGestion = null;

    #[ORM\Column(name: 'parIntervention', type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $parIntervention = null;

    #[ORM\Column(name: 'maxParIntervention', type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $maxParIntervention = null;

    #[ORM\Column(name: 'KMenfants', type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $kmEnfants = null;

    #[ORM\Column(name: 'abonnement', type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $abonnement = null;

    /** Format YYYY-MM ex: '2025-07' */
    #[ORM\Column(name: 'dateDebut', length: 7)]
    private ?string $dateDebut = null;

    public function getId(): ?int { return $this->id; }

    public function getAlheureGe(): ?string { return $this->alheureGe; }
    public function setAlheureGe(string $alheureGe): static { $this->alheureGe = $alheureGe; return $this; }

    public function getAlheureM(): ?string { return $this->alheureM; }
    public function setAlheureM(?string $alheureM): static { $this->alheureM = $alheureM; return $this; }

    public function getFraisGestion(): ?string { return $this->fraisGestion; }
    public function setFraisGestion(string $fraisGestion): static { $this->fraisGestion = $fraisGestion; return $this; }

    public function getParIntervention(): ?string { return $this->parIntervention; }
    public function setParIntervention(string $parIntervention): static { $this->parIntervention = $parIntervention; return $this; }

    public function getMaxParIntervention(): ?string { return $this->maxParIntervention; }
    public function setMaxParIntervention(string $maxParIntervention): static { $this->maxParIntervention = $maxParIntervention; return $this; }

    public function getKmEnfants(): ?string { return $this->kmEnfants; }
    public function setKmEnfants(string $kmEnfants): static { $this->kmEnfants = $kmEnfants; return $this; }

    public function getAbonnement(): ?string { return $this->abonnement; }
    public function setAbonnement(string $abonnement): static { $this->abonnement = $abonnement; return $this; }

    public function getDateDebut(): ?string { return $this->dateDebut; }
    public function setDateDebut(string $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function __toString(): string
    {
        return 'Tarif depuis ' . ($this->dateDebut ?? '?');
    }
}
