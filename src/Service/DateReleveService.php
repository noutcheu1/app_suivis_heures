<?php

namespace App\Service;

use DateInterval;
use DateTime;

class DateReleveService
{
    public function buildPeriode(string $type, int $offset): array
    {
        if ($type === 'ENFA') {
            $debut = new DateTime(date('Y-m-01'));
        } else {
            $debut = new DateTime(date('Y-m-25'));
            if ((int) date('d') < 25) {
                $debut->modify('-1 month');
            }
        }

        if ($offset !== 0) {
            $debut->modify("$offset month");
        }

        $fin = (clone $debut)->modify('+1 month -1 day');

        $jours = [];
        $cursor = clone $debut;

        $jours_base = [
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            7 => 'dimanche',
        ];


        while ($cursor <= $fin) {
            $jours[] = [
                'date' => $cursor->format('Y-m-d'),
                'jour' => $jours_base[(int)$cursor->format('N')],
                'numeroJour' => (int) $cursor->format('j'),
                'semaine' => (int) $cursor->format('W'),
            ];
            $cursor->add(new DateInterval('P1D'));
        }

        return [$debut, $fin, $jours];
    }

    public function getTotaux(array $familles, ?array $releve): array
    {
        $totalSec = array_sum(array_column($familles, 'totalSecondes'));
        $totalKm = array_sum(array_column($familles, 'totalKm'));

        return [
            'heuresMoisSecondes' => $totalSec,
            'kmMois' => $totalKm,
            'heureDehors' => $releve['heureDehors'] ?? null,
        ];
    }
}
