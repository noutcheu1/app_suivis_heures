<?php

namespace App\Service;

use App\Repository\FamilleRepository;
use App\Repository\IntervenantRepository;
use App\Repository\ProposerRepository;
use App\Repository\VacancesConfigRepository;
use App\Repository\VacancesReponseFamilleRepository;
use App\Repository\VacancesReponseIntervenantRepository;

/**
 * Vue d'ensemble des remplacements d'une campagne de congés.
 *
 * Répond aux 4 questions du pilotage RH :
 *   - qui est ABSENT (en congé) ?
 *   - qui est PRÉSENT et disponible pour remplacer ?
 *   - quels POSTES sont à pourvoir (créneaux libérés par les absences) ?
 *   - qui peut remplacer qui (candidats par poste, même service, proximité) ?
 *
 * Aucune assignation n'est stockée : la page présente l'information, l'admin décide.
 */
class RemplacementService
{
    public function __construct(
        private VacancesConfigRepository             $configRepo,
        private VacancesReponseIntervenantRepository $reponseInterRepo,
        private VacancesReponseFamilleRepository     $reponseFamRepo,
        private ProposerRepository                   $proposerRepo,
        private IntervenantRepository                $intervenantRepo,
        private FamilleRepository                    $familleRepo,
    ) {}

    private const JOURS_ORDRE = [
        'lundi' => 1, 'mardi' => 2, 'mercredi' => 3, 'jeudi' => 4,
        'vendredi' => 5, 'samedi' => 6, 'dimanche' => 7,
    ];

    /**
     * @return array{campagne: object, absents: list<array>, disponibles: list<array>, postes: list<array>, stats: array}|null
     */
    public function planPourCampagne(int $configId): ?array
    {
        $campagne = $this->configRepo->find($configId);
        if (!$campagne) {
            return null;
        }

        $reponses = $this->reponseInterRepo->findByConfig($configId);

        // Réponses familles indexées (situation + souhait de remplacement).
        $repFam = [];
        foreach ($this->reponseFamRepo->findByConfig($configId) as $rf) {
            $repFam[(string) $rf->getNumFam()] = $rf;
        }

        // Pool des remplaçants disponibles (présents), avec leurs compétences.
        $disponibles = [];
        foreach ($reponses as $r) {
            if ($r->getDisponibleRemplacement() === true) {
                $disponibles[] = $this->detailDispo($r->getNumInter(), $r->getCommentaire());
            }
        }
        usort($disponibles, fn ($a, $b) => strcmp($a['nom'], $b['nom']));

        // Absences → postes à pourvoir.
        $absents = [];
        $postes  = [];
        $nbAPourvoir = 0;
        foreach ($reponses as $r) {
            if (!$r->getDateDebutConge()) {
                continue;
            }
            $inter = $this->intervenantRepo->findInfosIntervenant($r->getNumInter());
            $nom   = $inter ? trim($inter->getPrenom() . ' ' . $inter->getNom()) : ('#' . $r->getNumInter());

            // Créneaux de cet intervenant, groupés par famille + service.
            $groupes = [];
            foreach ($this->proposerRepo->findActivesByIntervenant($r->getNumInter()) as $p) {
                $type = strtoupper($p->getTypePrestation());
                if ($type !== 'MENA') {
                    continue; // remplacement uniquement pour le ménage
                }
                $numFam = (string) $p->getNumeroFamille();
                $cle    = $numFam . '|' . $type;
                $groupes[$cle]['numFam'] = $numFam;
                $groupes[$cle]['type']   = $type;
                $jour = mb_strtolower(trim($p->getJour()));
                $groupes[$cle]['creneaux'][] = [
                    'ordre' => self::JOURS_ORDRE[$jour] ?? 9,
                    'texte' => ucfirst($jour) . ' ' . $p->getHeureDebut()->format('H:i')
                               . ($p->getHeureFin() ? '–' . $p->getHeureFin()->format('H:i') : ''),
                ];
            }

            $postesAbsent = [];
            foreach ($groupes as $g) {
                usort($g['creneaux'], fn ($a, $b) => $a['ordre'] <=> $b['ordre']);
                $fam = $this->familleRepo->findByNumero($g['numFam']);
                $rf  = $repFam[$g['numFam']] ?? null;

                $familleAbsente = $rf && $rf->getSituation() === 'absence';
                $familleSuspend = $rf && $rf->getSouhaiteRemplacement() === false;
                $statut = $familleSuspend ? 'suspend' : ($familleAbsente ? 'famille_absente' : 'a_pourvoir');

                $suggestions = $statut === 'a_pourvoir'
                    ? $this->suggestionsPour($g['type'], $fam?->getVille(), $disponibles)
                    : [];
                if ($statut === 'a_pourvoir') {
                    $nbAPourvoir++;
                }

                $poste = [
                    'interNom'    => $nom,
                    'interNum'    => $r->getNumInter(),
                    'debut'       => $r->getDateDebutConge(),
                    'fin'         => $r->getDateFinConge(),
                    'famNum'      => $g['numFam'],
                    'famNom'      => $fam?->getNomFamille() ?: ('Famille ' . $g['numFam']),
                    'famVille'    => $fam?->getVille(),
                    'type'        => $g['type'],
                    'creneaux'    => array_column($g['creneaux'], 'texte'),
                    'statut'      => $statut,
                    'suggestions' => $suggestions,
                ];
                $postes[]       = $poste;
                $postesAbsent[] = $poste;
            }

            $absents[] = [
                'nom'    => $nom,
                'num'    => $r->getNumInter(),
                'debut'  => $r->getDateDebutConge(),
                'fin'    => $r->getDateFinConge(),
                'postes' => $postesAbsent,
                'commentaire' => $r->getCommentaire(),
            ];
        }

        // Postes à pourvoir en priorité (non couverts d'abord), puis par date.
        $ordreStatut = ['a_pourvoir' => 0, 'suspend' => 1, 'famille_absente' => 2];
        usort($postes, function ($a, $b) use ($ordreStatut) {
            return ($ordreStatut[$a['statut']] <=> $ordreStatut[$b['statut']])
                ?: ($a['debut'] <=> $b['debut']);
        });
        usort($absents, fn ($a, $b) => $a['debut'] <=> $b['debut']);

        return [
            'campagne'    => $campagne,
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

    private function detailDispo(int $numInter, ?string $commentaire): array
    {
        $inter = $this->intervenantRepo->findInfosIntervenant($numInter);
        $types = [];
        $fams  = []; // familles déjà assurées → « charge » de la remplaçante
        foreach ($this->proposerRepo->findActivesByIntervenant($numInter) as $p) {
            $t = strtoupper($p->getTypePrestation());
            if ($t === 'MENA') {
                $types[$t] = true;
                $fams[(string) $p->getNumeroFamille()] = true;
            }
        }

        return [
            'num'         => $numInter,
            'nom'         => $inter ? trim($inter->getPrenom() . ' ' . $inter->getNom()) : ('#' . $numInter),
            'ville'       => $inter?->getVille(),
            'tel'         => $inter?->getTelPortable(),
            'types'       => array_keys($types),
            'charge'      => count($fams),
            'commentaire' => $commentaire,
        ];
    }

    private function suggestionsPour(string $type, ?string $villeFamille, array $disponibles): array
    {
        $out = [];
        foreach ($disponibles as $d) {
            if (!in_array($type, $d['types'], true)) {
                continue;
            }
            $proche = $villeFamille && $d['ville']
                && mb_strtolower(trim($villeFamille)) === mb_strtolower(trim($d['ville']));
            $out[] = $d + ['proche' => $proche];
        }
        // Même ville d'abord, puis la moins chargée, puis ordre alphabétique.
        usort($out, fn ($a, $b) => ($b['proche'] <=> $a['proche'])
            ?: ($a['charge'] <=> $b['charge'])
            ?: strcmp($a['nom'], $b['nom']));
        return $out;
    }
}
