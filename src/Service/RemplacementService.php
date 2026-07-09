<?php

namespace App\Service;

use App\Entity\Horaire\Conge;
use App\Repository\CongeRepository;
use App\Repository\FamilleRepository;
use App\Repository\IntervenantDispoRepository;
use App\Repository\IntervenantRepository;
use App\Repository\ProposerRepository;
use App\Repository\VacancesConfigRepository;

/**
 * Vue d'ensemble des remplacements d'une campagne — source de vérité : `conge`
 * (absences VALIDÉES) + `intervenant_dispo` (disponibilité générale).
 *
 * Aide à la décision : qui est absent, qui est disponible et SUR QUELLE PÉRIODE
 * (à partir de quand, en dehors de ses propres congés), quels postes à pourvoir.
 */
class RemplacementService
{
    public function __construct(
        private VacancesConfigRepository    $configRepo,
        private CongeRepository             $congeRepo,
        private IntervenantDispoRepository  $dispoRepo,
        private ProposerRepository          $proposerRepo,
        private IntervenantRepository       $intervenantRepo,
        private FamilleRepository           $familleRepo,
    ) {}

    private const JOURS_ORDRE = [
        'lundi' => 1, 'mardi' => 2, 'mercredi' => 3, 'jeudi' => 4,
        'vendredi' => 5, 'samedi' => 6, 'dimanche' => 7,
    ];

    public function planPourCampagne(int $configId): ?array
    {
        $campagne = $this->configRepo->find($configId);
        if (!$campagne) {
            return null;
        }

        // Fenêtre de la campagne (fallback : aujourd'hui → +3 mois si non définie).
        $pDebut = $campagne->getDateDebut() ?: new \DateTimeImmutable('today');
        $pFin   = $campagne->getDateFin()   ?: new \DateTimeImmutable('today +3 months');

        // Congés VALIDÉS intervenants sur la période, groupés par intervenant.
        $congesParInter = [];
        foreach ($this->congeRepo->findActifsSurPeriode(Conge::PERSONNE_INTERVENANT, $pDebut, $pFin) as $c) {
            if ($c->estValide()) {
                $congesParInter[(int) $c->getPersonneId()][] = $c;
            }
        }

        // Congés familles sur la période (pour suspension), indexés par famille.
        $congesFam = [];
        foreach ($this->congeRepo->findActifsSurPeriode(Conge::PERSONNE_FAMILLE, $pDebut, $pFin) as $c) {
            $congesFam[(string) $c->getPersonneId()][] = $c;
        }

        // ── Vivier des disponibles (déclarés dispo ET faisant du ménage) ──────
        $menaActifs = array_flip($this->proposerRepo->findNumsSalarieActifsParType('MENA'));
        $disponibles = [];
        foreach ($this->dispoRepo->numsDisponibles() as $num) {
            if (!isset($menaActifs[$num])) {
                continue; // ne fait pas de ménage → hors sujet
            }
            $disponibles[$num] = $this->detailDispo($num, $congesParInter[$num] ?? [], $pDebut, $pFin);
        }
        usort($disponibles, fn ($a, $b) => strcmp($a['nom'], $b['nom']));

        // ── Absences → postes à pourvoir ──────────────────────────────────────
        $absents = [];
        $postes  = [];
        $nbAPourvoir = 0;
        foreach ($congesParInter as $num => $conges) {
            $inter = $this->intervenantRepo->findInfosIntervenant($num);
            $nom   = $inter ? trim($inter->getPrenom() . ' ' . $inter->getNom()) : ('#' . $num);

            // Créneaux ménage de l'intervenant, groupés par famille.
            $creneauxFam = [];
            foreach ($this->proposerRepo->findActivesByIntervenant($num) as $p) {
                if (strtoupper($p->getTypePrestation()) !== 'MENA') {
                    continue;
                }
                $numFam = (string) $p->getNumeroFamille();
                $jour   = mb_strtolower(trim($p->getJour()));
                $creneauxFam[$numFam][] = [
                    'ordre' => self::JOURS_ORDRE[$jour] ?? 9,
                    'texte' => ucfirst($jour) . ' ' . $p->getHeureDebut()->format('H:i')
                               . ($p->getHeureFin() ? '–' . $p->getHeureFin()->format('H:i') : ''),
                ];
            }

            $postesAbsent = [];
            // Un poste par (congé × famille).
            foreach ($conges as $conge) {
                foreach ($creneauxFam as $numFam => $creneaux) {
                    usort($creneaux, fn ($a, $b) => $a['ordre'] <=> $b['ordre']);
                    $fam = $this->familleRepo->findByNumero($numFam);

                    $suspend = false;
                    foreach ($congesFam[$numFam] ?? [] as $cf) {
                        if ($cf->getSouhaiteRemplacement() === false || $cf->getMaintienPrestation() === false) {
                            if ($this->chevauche($cf->getDateDebut(), $cf->getDateFin(), $conge->getDateDebut(), $conge->getDateFin())) {
                                $suspend = true;
                            }
                        }
                    }
                    $statut = $suspend ? 'suspend' : 'a_pourvoir';

                    $suggestions = $statut === 'a_pourvoir'
                        ? $this->suggestionsLibres($conge->getDateDebut(), $conge->getDateFin(), $num, $fam?->getVille(), $disponibles)
                        : [];
                    if ($statut === 'a_pourvoir') {
                        $nbAPourvoir++;
                    }

                    $poste = [
                        'interNom'    => $nom,
                        'debut'       => $conge->getDateDebut(),
                        'fin'         => $conge->getDateFin(),
                        'famNum'      => $numFam,
                        'famNom'      => $fam?->getNomFamille() ?: ('Famille ' . $numFam),
                        'famVille'    => $fam?->getVille(),
                        'type'        => 'MENA',
                        'creneaux'    => array_column($creneaux, 'texte'),
                        'statut'      => $statut,
                        'suggestions' => $suggestions,
                    ];
                    $postes[]       = $poste;
                    $postesAbsent[] = $poste;
                }
            }

            $absents[] = [
                'nom'    => $nom,
                'num'    => $num,
                'debut'  => $conges[0]->getDateDebut(),
                'fin'    => $conges[0]->getDateFin(),
                'postes' => $postesAbsent,
            ];
        }

        $ordreStatut = ['a_pourvoir' => 0, 'suspend' => 1, 'famille_absente' => 2];
        usort($postes, fn ($a, $b) => ($ordreStatut[$a['statut']] <=> $ordreStatut[$b['statut']]) ?: ($a['debut'] <=> $b['debut']));
        usort($absents, fn ($a, $b) => $a['debut'] <=> $b['debut']);

        return [
            'campagne'    => $campagne,
            'periode'     => ['debut' => $pDebut, 'fin' => $pFin],
            'absents'     => $absents,
            'disponibles' => $disponibles,
            'postes'      => $postes,
            'stats' => [
                'absents'     => count($absents),
                'disponibles' => count($disponibles),
                'postes'      => count($postes),
                'aPourvoir'   => $nbAPourvoir,
                'sansDispo'   => count(array_filter($postes, fn ($p) => $p['statut'] === 'a_pourvoir' && !$p['suggestions'])),
            ],
        ];
    }

    /**
     * Détail d'un disponible + sa FENÊTRE de disponibilité sur la période :
     * à partir de quand il est libre et ses éventuelles indisponibilités (congés).
     *
     * @param Conge[] $conges congés de l'intervenant sur la période
     */
    private function detailDispo(int $num, array $conges, \DateTimeInterface $pDebut, \DateTimeInterface $pFin): array
    {
        $inter = $this->intervenantRepo->findInfosIntervenant($num);
        $fams  = [];
        foreach ($this->proposerRepo->findActivesByIntervenant($num) as $p) {
            if (strtoupper($p->getTypePrestation()) === 'MENA') {
                $fams[(string) $p->getNumeroFamille()] = true;
            }
        }

        // Indisponibilités (congés) bornées à la période, triées.
        $indispos = [];
        foreach ($conges as $c) {
            $d = max($c->getDateDebut(), $pDebut);
            $f = min($c->getDateFin(), $pFin);
            if ($d <= $f) {
                $indispos[] = ['debut' => $d, 'fin' => $f];
            }
        }
        usort($indispos, fn ($a, $b) => $a['debut'] <=> $b['debut']);

        // « Disponible à partir de » : 1er jour de la période non couvert par un congé.
        $cursor = \DateTimeImmutable::createFromInterface($pDebut);
        foreach ($indispos as $w) {
            if ($w['debut'] <= $cursor && $cursor <= $w['fin']) {
                $cursor = \DateTimeImmutable::createFromInterface($w['fin'])->modify('+1 day');
            }
        }
        $dispoDes = $cursor <= $pFin ? $cursor : null; // null = jamais libre sur la période

        return [
            'num'      => $num,
            'nom'      => $inter ? trim($inter->getPrenom() . ' ' . $inter->getNom()) : ('#' . $num),
            'ville'    => $inter?->getVille(),
            'tel'      => $inter?->getTelPortable(),
            'charge'   => count($fams),
            'indispos' => $indispos,
            'dispoDes' => $dispoDes,
            'toujours' => empty($indispos),
        ];
    }

    /** Candidats libres pour un trou : dispo, sans congé chevauchant, même ville d'abord. */
    private function suggestionsLibres(\DateTimeInterface $debut, \DateTimeInterface $fin, int $exclure, ?string $villeFamille, array $disponibles): array
    {
        $out = [];
        foreach ($disponibles as $d) {
            if ($d['num'] === $exclure) {
                continue;
            }
            // Occupé si un de ses congés chevauche le trou.
            $occupe = false;
            foreach ($d['indispos'] as $w) {
                if ($this->chevauche($w['debut'], $w['fin'], $debut, $fin)) {
                    $occupe = true;
                    break;
                }
            }
            if ($occupe) {
                continue;
            }
            $proche = $villeFamille && $d['ville']
                && mb_strtolower(trim($villeFamille)) === mb_strtolower(trim($d['ville']));
            $out[] = $d + ['proche' => $proche];
        }
        usort($out, fn ($a, $b) => ($b['proche'] <=> $a['proche']) ?: ($a['charge'] <=> $b['charge']) ?: strcmp($a['nom'], $b['nom']));
        return $out;
    }

    private function chevauche(\DateTimeInterface $d1, \DateTimeInterface $f1, \DateTimeInterface $d2, \DateTimeInterface $f2): bool
    {
        return $d1 <= $f2 && $d2 <= $f1;
    }
}
