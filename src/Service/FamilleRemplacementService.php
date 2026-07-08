<?php

namespace App\Service;

use App\Entity\Principal\Famille;
use App\Repository\IntervenantRepository;
use App\Repository\ProposerRepository;
use App\Repository\VacancesConfigRepository;
use App\Repository\VacancesReponseFamilleRepository;
use App\Repository\VacancesReponseIntervenantRepository;

/**
 * Remplacements à prévoir POUR UNE FAMILLE, sur la campagne de congés active.
 *
 * Logique centrée famille : on ne liste pas les absences en général, on montre
 * l'impact des absences sur CETTE famille — quels de ses intervenants habituels
 * partent, quels créneaux se libèrent, et qui est disponible pour les couvrir.
 *
 * Renvoie null s'il n'y a aucun impact réel (→ la carte est masquée côté UI).
 */
class FamilleRemplacementService
{
    public function __construct(
        private VacancesConfigRepository             $configRepo,
        private VacancesReponseIntervenantRepository $reponseInterRepo,
        private VacancesReponseFamilleRepository     $reponseFamRepo,
        private ProposerRepository                   $proposerRepo,
        private IntervenantRepository                $intervenantRepo,
    ) {}

    private const JOURS_ORDRE = [
        'lundi' => 1, 'mardi' => 2, 'mercredi' => 3, 'jeudi' => 4,
        'vendredi' => 5, 'samedi' => 6, 'dimanche' => 7,
    ];

    /**
     * @return array{campagne: object, trous: list<array>, intervenantsAbsents: int, interventions: int, familleAbsente: bool, familleSuspend: bool}|null
     */
    public function pourFamille(Famille $famille): ?array
    {
        $campagne = $this->configRepo->findPourFicheFamille();
        if (!$campagne) {
            return null;
        }

        $numFam = (string) $famille->getNumeroFamille();

        // Réponses des intervenants sur cette campagne, indexées par numéro.
        $reponses = [];
        foreach ($this->reponseInterRepo->findByConfig($campagne->getId()) as $r) {
            $reponses[$r->getNumInter()] = $r;
        }
        if (!$reponses) {
            return null;
        }

        // Pool des remplaçants disponibles (avec leurs compétences ENFA/MENA).
        $disponibles = [];
        foreach ($reponses as $r) {
            if ($r->getDisponibleRemplacement() === true) {
                $disponibles[] = $this->detailDispo($r->getNumInter(), $r->getCommentaire());
            }
        }

        // Réponse de la famille : est-elle absente ? veut-elle un remplacement ?
        $familleAbsente = false;
        $familleSuspend = false; // a explicitement dit « je préfère suspendre »
        foreach ($this->reponseFamRepo->findByConfig($campagne->getId()) as $rf) {
            if ((string) $rf->getNumFam() !== $numFam) {
                continue;
            }
            $familleAbsente = $rf->getSituation() === 'absence';
            $familleSuspend = $rf->getSouhaiteRemplacement() === false;
        }

        // Créneaux de la famille, regroupés par intervenant + type de service.
        $groupes = [];
        foreach ($this->proposerRepo->findActivesByFamille($numFam) as $p) {
            $numInter = (int) $p->getNumSalarie();
            $rep = $reponses[$numInter] ?? null;
            if (!$rep || !$rep->getDateDebutConge()) {
                continue; // cet intervenant ne part pas en congé → pas d'impact
            }
            $type = strtoupper($p->getTypePrestation());
            if ($type !== 'MENA') {
                continue; // remplacement uniquement pour le ménage
            }
            $cle = $numInter . '|' . $type;
            $groupes[$cle]['numInter'] = $numInter;
            $groupes[$cle]['type']     = $type;
            $groupes[$cle]['debut']    = $rep->getDateDebutConge();
            $groupes[$cle]['fin']      = $rep->getDateFinConge();
            $jour = mb_strtolower(trim($p->getJour()));
            $groupes[$cle]['creneaux'][] = [
                'ordre' => self::JOURS_ORDRE[$jour] ?? 9,
                'texte' => ucfirst($jour) . ' ' . $p->getHeureDebut()->format('H:i')
                           . ($p->getHeureFin() ? '–' . $p->getHeureFin()->format('H:i') : ''),
            ];
        }

        if (!$groupes) {
            return null; // aucun intervenant habituel en congé → carte masquée
        }

        $trous = [];
        $absents = [];
        foreach ($groupes as $g) {
            usort($g['creneaux'], fn ($a, $b) => $a['ordre'] <=> $b['ordre']);
            $inter = $this->intervenantRepo->findInfosIntervenant($g['numInter']);
            $absents[$g['numInter']] = true;

            $trous[] = [
                'interNom'    => $inter ? trim($inter->getPrenom() . ' ' . $inter->getNom()) : ('#' . $g['numInter']),
                'type'        => $g['type'],
                'debut'       => $g['debut'],
                'fin'         => $g['fin'],
                'creneaux'    => array_column($g['creneaux'], 'texte'),
                // Famille qui préfère suspendre → pas de remplaçant à proposer.
                'suggestions' => $familleSuspend ? [] : $this->suggestionsPour($g['type'], $famille->getVille(), $disponibles),
            ];
        }

        return [
            'campagne'            => $campagne,
            'trous'               => $trous,
            'intervenantsAbsents' => count($absents),
            'interventions'       => count($trous),
            'familleAbsente'      => $familleAbsente,
            'familleSuspend'      => $familleSuspend,
        ];
    }

    /** Détail d'un remplaçant disponible : nom, ville, tél, services assurés. */
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
            'nom'         => $inter ? trim($inter->getPrenom() . ' ' . $inter->getNom()) : ('#' . $numInter),
            'ville'       => $inter?->getVille(),
            'tel'         => $inter?->getTelPortable(),
            'types'       => array_keys($types),
            'charge'      => count($fams),
            'commentaire' => $commentaire,
        ];
    }

    /** Remplaçants qui assurent ce service ; même ville en premier (proximité). */
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
        usort($out, fn ($a, $b) => ($b['proche'] <=> $a['proche']) ?: strcmp($a['nom'], $b['nom']));
        return $out;
    }
}
