<?php

namespace App\Service;

use App\Entity\Principal\Famille;

class ReleveFamilleBuilder
{
    public function __construct(
        private FamilleService     $familleService,
        private IntervenantService $intervenantService,
    ) {}

    /**
     * Construit le tableau des relevés pour le template familles/facture.html.twig.
     *
     * @param array $facture Résultat de FactureService::calculerFacture()
     * @return array<int, array>
     */
    public function buildReleves(
        string   $numFam,
        ?Famille $famille,
        string   $moisAnnee,
        array    $facture,
        ?string  $typeFilter,
    ): array {
        [$year, $month] = array_map('intval', explode('-', $moisAnnee));

        $moisFrNames = [
            1=>'JANVIER', 2=>'FÉVRIER',  3=>'MARS',      4=>'AVRIL',
            5=>'MAI',     6=>'JUIN',     7=>'JUILLET',   8=>'AOÛT',
            9=>'SEPTEMBRE',10=>'OCTOBRE',11=>'NOVEMBRE', 12=>'DÉCEMBRE',
        ];
        $moisFr   = $moisFrNames[$month];
        $prevDate = (new \DateTime(sprintf('%04d-%02d-01', $year, $month)))->modify('-1 month');
        $moisPrevFr = $moisFrNames[(int)$prevDate->format('m')];
        $yearPrev   = (int)$prevDate->format('Y');

        $periodeDebut = '25 ' . $moisPrevFr . ' ' . $yearPrev;
        $periodeFin   = '24 ' . $moisFr . ' ' . $year;
        $dateLimite   = '30 ' . $moisFr . ' ' . $year;

        // Enfants et tranche d'âge priorité au plus jeune
        $enfants = $this->familleService->getEnfantsByFamille($numFam, true);
        $refDate = new \DateTime($moisAnnee . '-01');
        $moins3 = $trois6 = $plus6 = [];

        foreach ($enfants as $enfant) {
            if (!$enfant->getDateNaiss()) continue;
            $nom = $enfant->getNomComplet();
            match ($enfant->getTranche($refDate)) {
                'moins_3ans' => $moins3[] = $nom,
                '3_6ans'     => $trois6[] = $nom,
                default      => $plus6[]  = $nom,
            };
        }
        $trancheAge = !empty($moins3) ? 'moins_3ans' : (!empty($trois6) ? '3_6ans' : 'plus_6ans');

        // CAF : numéro d'allocataire ET enfant < 6 ans
        $avecCaf = !empty($famille?->getNumAlloc()) && (!empty($moins3) || !empty($trois6));

        $famBloc = [
            'numFam'          => $numFam,
            'nom'             => $famille?->getNomFamille() ?? $numFam,
            'adresse'         => $famille?->getAdresse()    ?? '',
            'codePostal'      => $famille?->getCodePostal() ?? '',
            'ville'           => $famille?->getVille()      ?? '',
            'avecCaf'         => $avecCaf,
            'numeroCaf'       => $famille?->getNumAlloc()   ?? '',
            'telephone'       => $famille?->getTelDom()     ?? '',
            'enfantMoins3ans' => implode(', ', $moins3),
            'enfant3a6ans'    => implode(', ', $trois6),
            'enfantPlus6ans'  => implode(', ', $plus6),
        ];

        $tarif    = $facture['tarif'] ?? null;
        $parInter = (float)($tarif?->getParIntervention()    ?? 1.5);
        $maxInter = (float)($tarif?->getMaxParIntervention() ?? 15.0);
        $tarifKm  = (float)($tarif?->getKmEnfants()          ?? 0.35);
        $tarifAbo = (float)($tarif?->getAbonnement()         ?? 2.0);

        $nomIntervenantesGE   = $this->buildIntervenantes($facture['prestationsGE']   ?? []);
        $nomIntervenantesMENA = $this->buildIntervenantes($facture['prestationsMENA'] ?? []);

        $emptyPaiement = [
            'coutHoraire'       => 0,   'nbHeuresRealisees' => 0,       'montantPrestation' => 0,
            'fraisGestion'      => 0,   'parIntervention'   => $parInter,'maxIntervention'  => $maxInter,
            'nbInterventions'   => 0,   'kmTotal'           => 0,        'tarifKm'          => $tarifKm,
            'montantKm'         => 0,   'abonnement'        => $tarifAbo,'montantAbonnement'=> 0,
            'montantSupl'       => 0,   'libeleSupl'        => '',        'modeReglement'    => '',
            'numCheque'         => '',  'nbrCESU'           => '',        'sommeAPayer'      => 0,
        ];

        $releves = [];

        if (!empty($facture['prestationsGE'])) {
            $joursGE   = $this->buildJours($facture['prestationsGE'], $year, $month);
            $heuresGE  = $this->joursTotalFloat($joursGE);
            $montantGE = round($heuresGE * ($facture['tauxGE'] ?? 0), 2);
            $sommeGE   = round(
                $montantGE
                + ($facture['fraisGestion'] ?? 0)
                + ($facture['montantKm']    ?? 0)
                + ($facture['abonnement']   ?? 0)
                + ($facture['montantSupl']  ?? 0),
                2
            );
            $releves[] = $this->makeReleve(
                "GARDE D'ENFANTS",
                $moisFr, $year, $periodeDebut, $periodeFin, $dateLimite,
                $trancheAge,
                array_merge($famBloc, ['codeClient' => $famille?->getNumeroFamille() ?? $numFam]),
                $nomIntervenantesGE,
                $joursGE,
                $this->joursTotal($joursGE),
                array_merge($emptyPaiement, [
                    'coutHoraire'       => $facture['tauxGE']          ?? 0,
                    'nbHeuresRealisees' => $heuresGE,
                    'montantPrestation' => $montantGE,
                    'fraisGestion'      => $facture['fraisGestion']     ?? 0,
                    'nbInterventions'   => $facture['nbInterventions']  ?? 0,
                    'kmTotal'           => $facture['kmTotal']          ?? 0,
                    'montantKm'         => $facture['montantKm']        ?? 0,
                    'montantAbonnement' => $facture['abonnement']       ?? 0,
                    'montantSupl'       => $facture['montantSupl']      ?? 0,
                    'libeleSupl'        => $facture['libeleSupl']       ?? '',
                    'modeReglement'     => $facture['releveGE']?->getTypeReglement() ?? '',
                    'numCheque'         => $facture['releveGE']?->getNumCheque()     ?? '',
                    'nbrCESU'           => $facture['releveGE']?->getNbrCESU()       ?? '',
                    'sommeAPayer'       => $sommeGE,
                ]),
                $facture['releveGE'] ?? null
            );
        }

        if (!empty($facture['prestationsMENA'])) {
            $joursMENA   = $this->buildJours($facture['prestationsMENA'], $year, $month);
            $heuresMENA  = $this->joursTotalFloat($joursMENA);
            $montantMENA = round($heuresMENA * ($facture['tauxMENA'] ?? 0), 2);
            $sommeM      = round(
                $montantMENA
                + ($facture['abonnement']  ?? 0)
                + ($facture['montantSupl'] ?? 0),
                2
            );
            $releves[] = $this->makeReleve(
                'MÉNAGE',
                $moisFr, $year, $periodeDebut, $periodeFin, $dateLimite,
                null,
                array_merge($famBloc, ['codeClient' => $famille?->getPmFamille() ?? $numFam]),
                $nomIntervenantesMENA,
                $joursMENA,
                $this->joursTotal($joursMENA),
                array_merge($emptyPaiement, [
                    'coutHoraire'       => $facture['tauxMENA']         ?? 0,
                    'nbHeuresRealisees' => $heuresMENA,
                    'montantPrestation' => $montantMENA,
                    'nbInterventions'   => $facture['nbInterventions']  ?? 0,
                    'montantAbonnement' => $facture['abonnement']       ?? 0,
                    'montantSupl'       => $facture['montantSupl']      ?? 0,
                    'libeleSupl'        => $facture['libeleSupl']       ?? '',
                    'modeReglement'     => $facture['releveMENA']?->getTypeReglement() ?? '',
                    'numCheque'         => $facture['releveMENA']?->getNumCheque()     ?? '',
                    'nbrCESU'           => $facture['releveMENA']?->getNbrCESU()       ?? '',
                    'sommeAPayer'       => $sommeM,
                ]),
                $facture['releveMENA'] ?? null
            );
        }

        if (empty($releves)) {
            $releves[] = $this->makeReleve(
                'Aucune prestation ce mois —',
                $moisFr, $year, $periodeDebut, $periodeFin, $dateLimite,
                null,
                array_merge($famBloc, ['codeClient' => $numFam]),
                [],
                $this->buildJours([], $year, $month),
                '',
                $emptyPaiement,
                null
            );
        }

        if ($typeFilter !== null) {
            $keyword = $typeFilter === 'GE' ? 'GARDE' : 'MÉNAGE';
            $releves = array_values(array_filter(
                $releves,
                fn($r) => str_contains(strtoupper($r['typePrestation']), $keyword)
            ));

            if (empty($releves)) {
                $label      = $typeFilter === 'GE' ? "GARDE D'ENFANTS" : 'MÉNAGE';
                $codeClient = $typeFilter === 'GE'
                    ? ($famille?->getNumeroFamille() ?? $numFam)
                    : ($famille?->getPmFamille()     ?? $numFam);
                $releves[] = $this->makeReleve(
                    $label,
                    $moisFr, $year, $periodeDebut, $periodeFin, $dateLimite,
                    null,
                    array_merge($famBloc, ['codeClient' => $codeClient]),
                    [],
                    $this->buildJours([], $year, $month),
                    '',
                    $emptyPaiement,
                    null
                );
            }
        }

        return $releves;
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    private function makeReleve(
        string  $typePrestation,
        string  $moisFr,
        int     $year,
        string  $periodeDebut,
        string  $periodeFin,
        string  $dateLimite,
        ?string $trancheAge,
        array   $famille,
        array   $intervenantes,
        array   $jours,
        string  $totalLabel,
        array   $paiement,
        mixed   $releve,
    ): array {
        return [
            'typePrestation' => $typePrestation,
            'mois'           => $moisFr,
            'annee'          => (string) $year,
            'periodeDebut'   => $periodeDebut,
            'periodeFin'     => $periodeFin,
            'dateLimite'     => $dateLimite,
            'trancheAge'     => $trancheAge,
            'famille'        => $famille,
            'intervenantes'  => $intervenantes,
            'jours'          => $jours,
            'totalLabel'     => $totalLabel,
            'paiement'       => $paiement,
            'releve'         => $releve,
        ];
    }

    private function buildIntervenantes(array $prestations): array
    {
        $byInter = [];
        foreach ($prestations as $p) {
            $byInter[$p->getNumInter()][] = $p;
        }
        ksort($byInter);

        $noms = [];
        foreach ($byInter as $numInter => $prestInter) {
            $interv = $this->intervenantService->getIntervenantParNumSalarie((string)$numInter);
            if (!$interv) continue;
            $nom = $interv->getNomCompletInter();
            if (!empty(array_filter($prestInter, fn($p) => !$p->getDeclarerLeFam()))) {
                $nom .= ' (heures non saisies par la famille)';
            }
            $noms[] = $nom;
        }

        return $noms;
    }

    private function buildJours(array $prestations, int $year, int $month): array
    {
        $start = (new \DateTime(sprintf('%04d-%02d-25', $year, $month)))->modify('-1 month');
        $end   = new \DateTime(sprintf('%04d-%02d-24', $year, $month));

        $prestsByDate = [];
        foreach ($prestations as $p) {
            $prestsByDate[$p->getDatePresta()->format('Y-m-d')][] = $p;
        }

        $dayNames = ['DIM','LUN','MAR','MER','JEU','VEN','SAM'];
        $jours    = [];
        $cur      = clone $start;

        while ($cur <= $end) {
            $dateKey   = $cur->format('Y-m-d');
            $dayPrests = $prestsByDate[$dateKey] ?? [];
            $heureDebut = $heureFin = $nbHeures = '';

            if (!empty($dayPrests)) {
                usort($dayPrests, fn($a, $b) => $a->getHeureDebutPresta() <=> $b->getHeureDebutPresta());
                $heureDebut = reset($dayPrests)->getHeureDebutPresta()?->format('H:i') ?? '';
                $heureFin   = end($dayPrests)->getHeureFinPresta()?->format('H:i')    ?? '';
                $totalSec   = 0;
                foreach ($dayPrests as $p) {
                    $d = $p->getHeureDebutPresta();
                    $f = $p->getHeureFinPresta();
                    if ($d && $f) {
                        $sec = (int)$f->format('H') * 3600 + (int)$f->format('i') * 60
                             - (int)$d->format('H') * 3600 - (int)$d->format('i') * 60;
                        $totalSec += max(0, $sec);
                    }
                }
                if ($totalSec > 0) {
                    $nbHeures = sprintf('%dh%02d', intdiv($totalSec, 3600), ($totalSec % 3600) / 60);
                }
            }

            $jours[] = [
                'jourSemaine' => $dayNames[(int)$cur->format('w')],
                'numero'      => $cur->format('d'),
                'ferie'       => false,
                'heureDebut'  => $heureDebut,
                'heureFin'    => $heureFin,
                'nbHeures'    => $nbHeures,
                'observation' => '',
            ];

            $cur->modify('+1 day');
        }

        return $jours;
    }

    private function joursTotal(array $jours): string
    {
        $totalSec = 0;
        foreach ($jours as $j) {
            if ($j['nbHeures'] !== '') {
                [$h, $m] = explode('h', $j['nbHeures']);
                $totalSec += (int)$h * 3600 + (int)$m * 60;
            }
        }
        return $totalSec > 0
            ? sprintf('%dh%02d', intdiv($totalSec, 3600), ($totalSec % 3600) / 60)
            : '';
    }

    private function joursTotalFloat(array $jours): float
    {
        $totalSec = 0;
        foreach ($jours as $j) {
            if ($j['nbHeures'] !== '') {
                [$h, $m] = explode('h', $j['nbHeures']);
                $totalSec += (int)$h * 3600 + (int)$m * 60;
            }
        }
        return round($totalSec / 3600, 4);
    }
}
