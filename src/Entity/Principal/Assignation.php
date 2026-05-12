<?php

namespace App\Entity\Principal;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;



/**
 * Représente une ligne de la table `proposer` (principal DB).
 * Table de liaison Famille ↔ Intervenant avec plannings hebdomadaires.
 * Pas de PK auto → on ne mappe pas via Doctrine ORM, on utilise DBAL.
 */
final class Assignation
{
    public function __construct(
        public readonly string $typePrestation,      // idPresta_Prestations : ENFA, MENA, BURO…
        public readonly int    $numSalarie,          // numSalarie_Intervenants
        public readonly string $numeroFamille,       // numero_Famille
        public readonly string $typeAdh,             // idADH_TypeADH : MAND, PREST…
        public readonly string $jour,                // jour_Proposer : lundi, mardi…
        public readonly string $heureDebut,          // hDeb_Proposer  (HH:MM:SS)
        public readonly ?string $heureFin,           // hFin_Proposer
        public readonly \DateTimeImmutable $dateDeb, // DateDeb_Proposer
        public readonly ?\DateTimeImmutable $dateFin,// dateFin_Proposer (null = actif)
        public readonly string $statut,              // Statut_Proposer
        public readonly ?bool $validIntervenant,     // validInterv_Proposer
        public readonly ?bool $validFamille,         // validFamille_Proposer
        public readonly ?string $modalites,          // modalites_Proposer
        public readonly ?int $frequence,             // frequence_Proposer
    ) {}

    public function isActive(): bool
    {
        if ($this->dateFin === null) {
            return true;
        }
        return $this->dateFin >= new \DateTimeImmutable('today');
    }

    public function getLabelPrestation(): string
    {
        return match ($this->typePrestation) {
            'ENFA' => 'Garde d\'enfants',
            'MENA' => 'Ménage',
            'BURO' => 'Bureau',
            'DRCT' => 'En direct',
            'DISP' => 'Pas disponible',
            default => $this->typePrestation,
        };
    }

    public static function fromRow(array $row): self
    {
        $parseDate = static function (?string $d): ?\DateTimeImmutable {
            if (!$d || $d === '0000-00-00') {
                return null;
            }
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $d);
            return $dt ?: null;
        };

        return new self(
            typePrestation:  $row['idPresta_Prestations'],
            numSalarie:      (int)$row['numSalarie_Intervenants'],
            numeroFamille:   $row['numero_Famille'],
            typeAdh:         $row['idADH_TypeADH'],
            jour:            $row['jour_Proposer'],
            heureDebut:      $row['hDeb_Proposer'],
            heureFin:        $row['hFin_Proposer'] ?? null,
            dateDeb:         $parseDate($row['DateDeb_Proposer']) ?? new \DateTimeImmutable('1970-01-01'),
            dateFin:         $parseDate($row['dateFin_Proposer'] ?? null),
            statut:          $row['Statut_Proposer'] ?? 'En attente',
            validIntervenant:isset($row['validInterv_Proposer']) ? (bool)$row['validInterv_Proposer'] : null,
            validFamille:    isset($row['validFamille_Proposer']) ? (bool)$row['validFamille_Proposer'] : null,
            modalites:       $row['modalites_Proposer'] ?? null,
            frequence:       isset($row['frequence_Proposer']) ? (int)$row['frequence_Proposer'] : null,
        );
    }
}
