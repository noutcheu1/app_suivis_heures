<?php

namespace App\Entity\Horaire;

use App\Repository\TarifFamilleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Taux horaire spécifique à une famille, horodaté.
 * Remplace le marqueur "V" (variable) du tarif global pour les familles
 * dont le taux est défini manuellement (ex-Access).
 */
#[ORM\Entity(repositoryClass: TarifFamilleRepository::class)]
#[ORM\Table(name: 'tauxhoraire')]
class TarifFamille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Référence famille.numero_Famille */
    #[ORM\Column(name: 'numFam', length: 10)]
    private ?string $numFam = null;

    /** 'GE' (garde d'enfants) ou 'M' (ménage) */
    #[ORM\Column(name: 'typePresta', length: 4)]
    private ?string $typePresta = null;

    #[ORM\Column(name: 'tauxHoraire', type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $tauxHoraire = null;

    /** Mois d'entrée en vigueur, format YYYY-MM */
    #[ORM\Column(name: 'dateDebut', length: 7)]
    private ?string $dateDebut = null;

    public function getId(): ?int { return $this->id; }

    public function getNumFam(): ?string { return $this->numFam; }
    public function setNumFam(string $numFam): static { $this->numFam = $numFam; return $this; }

    public function getTypePresta(): ?string { return $this->typePresta; }
    public function setTypePresta(string $typePresta): static { $this->typePresta = $typePresta; return $this; }

    public function getTauxHoraire(): ?string { return $this->tauxHoraire; }
    public function setTauxHoraire(string $tauxHoraire): static { $this->tauxHoraire = $tauxHoraire; return $this; }

    public function getDateDebut(): ?string { return $this->dateDebut; }
    public function setDateDebut(string $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getLabelPresta(): string
    {
        return match ($this->typePresta) {
            'GE', 'ENFA' => "Garde d'enfants",
            'M',  'MENA' => 'Ménage',
            default      => $this->typePresta ?? '?',
        };
    }
}
