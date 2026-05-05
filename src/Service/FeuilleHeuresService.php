<?php

namespace App\Service;

use DateTime;

class FeuilleHeuresService
{
    private const JOURS_FRANCAIS = [
        'Sunday' => 'dimanche',
        'Monday' => 'lundi',
        'Tuesday' => 'mardi',
        'Wednesday' => 'mercredi',
        'Thursday' => 'jeudi',
        'Friday' => 'vendredi',
        'Saturday' => 'samedi'
    ];

    private const MOIS_ABREGES = [
        1 => "janv.", 2 => "févr.", 3 => "mars", 4 => "avr.",
        5 => "mai", 6 => "juin", 7 => "juil.", 8 => "août",
        9 => "sept.", 10 => "oct.", 11 => "nov.", 12 => "déc."
    ];

    private const MOIS_COMPLETS = [
        1 => "JANVIER", 2 => "FÉVRIER", 3 => "MARS", 4 => "AVRIL",
        5 => "MAI", 6 => "JUIN", 7 => "JUILLET", 8 => "AOÛT",
        9 => "SEPTEMBRE", 10 => "OCTOBRE", 11 => "NOVEMBRE", 12 => "DÉCEMBRE"
    ];

    static public function getMoisByIndex(int $index) : string {
        return self::MOIS_COMPLETS[$index];
    }

    public function genererFiches(
        int $anneeDebut,
        int $moisDebut,
        int $anneeFin,
        int $moisFin,
        string $type
    ): array {
        $fiches = [];
        $anneeX = $anneeDebut;
        $moisX = $moisDebut;

        while ($anneeX <= $anneeFin && $moisX <= $moisFin) {
            $fiche = $this->genererFicheMois($anneeX, $moisX, $type);
            $fiches[] = $fiche;

            $moisX++;
            if ($moisX > 12) {
                $moisX = 1;
                $anneeX++;
            }
        }

        return $fiches;
    }

    private function genererFicheMois(int $annee, int $mois, string $type): array
    {
        // Calcul du premier jour selon le type
        if ($type === 'ENFA') {
            $premierJour = new DateTime("$annee-$mois-01");
        } else {
            $premierJour = new DateTime("$annee-$mois-25");
            $premierJour->modify('-1 month');
        }

        // Calcul du dernier jour
        $dernierJour = clone $premierJour;
        $dernierJour->modify('+1 month -1 day');

        // Génération des jours
        $jours = $this->genererJours($premierJour, $dernierJour, $type);

        return [
            'premierJour' => $premierJour,
            'dernierJour' => $dernierJour,
            'moisComplet' => self::MOIS_COMPLETS[(int)$dernierJour->format('n')],
            'annee' => $dernierJour->format('Y'),
            'jours' => $jours,
            'dateLimite' => $type === 'MENA' ? '25/26' : '30/31',
            'afficherKm' => $type === 'ENFA'
        ];
    }

    private function genererJours(DateTime $premierJour, DateTime $dernierJour, string $type): array
    {
        $jours = [];
        $jourCourant = clone $premierJour;
        $semaineEnCours = null;

        while ($jourCourant <= $dernierJour) {
            $numeroJour = (int)$jourCourant->format('j');
            $moisNum = (int)$jourCourant->format('n');
            $nomMois = self::MOIS_ABREGES[$moisNum];
            $nomJour = self::JOURS_FRANCAIS[date('l', strtotime($jourCourant->format('Y-m-d')))];
            $numeroSemaine = (int)$jourCourant->format('W');
            
            $diff = $jourCourant->diff($dernierJour);
            $nbrJoursDiff = $diff->days + 1;

            // Déterminer le rowspan de la semaine
            $rowspan = $this->calculerRowspanSemaine(
                $nomJour,
                $nbrJoursDiff,
                $numeroJour,
                $type
            );

            $afficherMois = ($jourCourant == $premierJour || $jourCourant == $dernierJour);

            $jours[] = [
                'nomJour' => $nomJour,
                'nomJourCourt' => strtoupper($nomJour[0]),
                'numeroJour' => $numeroJour,
                'nomMois' => $nomMois,
                'afficherMois' => $afficherMois,
                'numeroSemaine' => $numeroSemaine,
                'rowspan' => $rowspan,
                'nouvelleSemaine' => $rowspan > 0,
                'isDimanche' => $nomJour === 'dimanche'
            ];

            $jourCourant->modify('+1 day');
        }

        return $jours;
    }

    private function calculerRowspanSemaine(
        string $nomJour,
        int $nbrJoursDiff,
        int $numeroJour,
        string $type
    ): int {
        if ($nomJour === 'dimanche') {
            return 0; // Pas de cellule semaine pour dimanche
        }

        if ($nomJour === 'lundi') {
            return $nbrJoursDiff > 6 ? 6 : $nbrJoursDiff;
        }

        // Premier jour du mois pour ENFA ou 25e jour pour MENA
        $isPremierJour = ($type === 'ENFA' && $numeroJour === 1) 
                      || ($type === 'MENA' && $numeroJour === 25);

        if ($isPremierJour) {
            return match($nomJour) {
                'mardi' => 5,
                'mercredi' => 4,
                'jeudi' => 3,
                'vendredi' => 2,
                'samedi' => 1,
                default => 0
            };
        }

        return 0;
    }

    public function genererNomFichier(
        int $moisDebut,
        int $anneeDebut,
        int $moisFin,
        int $anneeFin
    ): string {
        if ($moisDebut === 1 && $moisFin === 12) {
            return (string) $anneeFin;
        }

        return "$moisDebut-$anneeDebut $moisFin-$anneeFin";
    }
}