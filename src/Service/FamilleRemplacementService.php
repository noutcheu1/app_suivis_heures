<?php

namespace App\Service;

use App\Entity\Horaire\Conge;
use App\Entity\Principal\Famille;
use App\Repository\CongeRepository;
use App\Repository\IntervenantRepository;
use App\Repository\ProposerRepository;

/**
 * Remplacements à prévoir POUR UNE FAMILLE — source de vérité : la table `conge`
 * (indépendante des campagnes). On croise :
 *   - les congés des intervenants MÉNAGE assignés à cette famille (→ trous) ;
 *   - le vivier des intervenants ménage LIBRES sur la période (→ remplaçants) ;
 *   - les congés de la famille elle-même (→ suspension éventuelle).
 *
 * Renvoie null s'il n'y a aucun trou à couvrir (→ carte masquée).
 */
class FamilleRemplacementService
{
    public function __construct(
        private CongeRepository       $congeRepo,
        private ProposerRepository    $proposerRepo,
        private IntervenantRepository $intervenantRepo,
        private \App\Repository\IntervenantDispoRepository $dispoRepo,
    ) {}

    private const JOURS_ORDRE = [
        'lundi' => 1, 'mardi' => 2, 'mercredi' => 3, 'jeudi' => 4,
        'vendredi' => 5, 'samedi' => 6, 'dimanche' => 7,
    ];

    /**
     * @return array{trous: list<array>, intervenantsAbsents: int, interventions: int}|null
     */
    public function pourFamille(Famille $famille): ?array
    {
        $numFam = (string) $famille->getNumeroFamille();
        $today  = new \DateTimeImmutable('today');

        // Créneaux ménage de la famille, groupés par intervenant assigné.
        $creneauxParInter = [];
        foreach ($this->proposerRepo->findActivesByFamille($numFam) as $p) {
            if (strtoupper($p->getTypePrestation()) !== 'MENA') {
                continue;
            }
            $num = (int) $p->getNumSalarie();
            $jour = mb_strtolower(trim($p->getJour()));
            $creneauxParInter[$num][] = [
                'ordre' => self::JOURS_ORDRE[$jour] ?? 9,
                'texte' => ucfirst($jour) . ' ' . $p->getHeureDebut()->format('H:i')
                           . ($p->getHeureFin() ? '–' . $p->getHeureFin()->format('H:i') : ''),
            ];
        }
        if (!$creneauxParInter) {
            return null;
        }

        $trous   = [];
        $absents = [];
        foreach ($creneauxParInter as $num => $creneaux) {
            // Congés à venir / en cours de cet intervenant.
            foreach ($this->congeRepo->findActifsByPersonne(Conge::PERSONNE_INTERVENANT, (string) $num) as $conge) {
                if ($conge->getDateFin() < $today) {
                    continue; // congé passé
                }
                if (!$conge->estValide()) {
                    continue; // seuls les congés VALIDÉS créent un trou confirmé
                }
                $inter = $this->intervenantRepo->findInfosIntervenant($num);

                // La famille suspend-elle SUR cette période ? (son propre congé)
                $suspend = false;
                foreach ($this->congeRepo->findChevauchant(Conge::PERSONNE_FAMILLE, $numFam, $conge->getDateDebut(), $conge->getDateFin()) as $cf) {
                    if ($cf->getSouhaiteRemplacement() === false || $cf->getMaintienPrestation() === false) {
                        $suspend = true;
                    }
                }

                usort($creneaux, fn ($a, $b) => $a['ordre'] <=> $b['ordre']);
                $absents[$num] = true;
                $trous[] = [
                    'interNom'    => $inter ? trim($inter->getPrenom() . ' ' . $inter->getNom()) : ('#' . $num),
                    'debut'       => $conge->getDateDebut(),
                    'fin'         => $conge->getDateFin(),
                    'creneaux'    => array_column($creneaux, 'texte'),
                    'suspend'     => $suspend,
                    'suggestions' => $suspend ? [] : $this->remplacantsLibres($conge->getDateDebut(), $conge->getDateFin(), $num, $famille->getVille()),
                ];
            }
        }

        if (!$trous) {
            return null;
        }

        return [
            'trous'               => $trous,
            'intervenantsAbsents' => count($absents),
            'interventions'       => count($trous),
        ];
    }

    /**
     * Intervenants ménage LIBRES sur [debut, fin] : ceux qui n'ont AUCUN congé qui
     * chevauche la période. Même ville d'abord, puis la moins chargée.
     *
     * @return list<array{nom:string, ville:?string, tel:?string, charge:int, proche:bool}>
     */
    private function remplacantsLibres(\DateTimeInterface $debut, \DateTimeInterface $fin, int $exclure, ?string $villeFamille): array
    {
        // Vivier = intervenants ménage ayant DÉCLARÉ être disponibles pour remplacer.
        $disponibles = array_flip($this->dispoRepo->numsDisponibles());

        $out = [];
        foreach ($this->proposerRepo->findNumsSalarieActifsParType('MENA') as $num) {
            if ($num === $exclure) {
                continue;
            }
            // Pas déclaré disponible aux remplacements → on ne le propose pas.
            if (!isset($disponibles[$num])) {
                continue;
            }
            // Occupé (congé VALIDÉ qui chevauche) → pas disponible sur cette période.
            if (!empty($this->congeRepo->findChevauchant(Conge::PERSONNE_INTERVENANT, (string) $num, $debut, $fin, null, [Conge::STATUT_VALIDE]))) {
                continue;
            }
            $inter = $this->intervenantRepo->findInfosIntervenant($num);
            if (!$inter) {
                continue;
            }
            $ville  = $inter->getVille();
            $proche = $villeFamille && $ville
                && mb_strtolower(trim($villeFamille)) === mb_strtolower(trim($ville));
            $out[] = [
                'nom'    => trim($inter->getPrenom() . ' ' . $inter->getNom()),
                'ville'  => $ville,
                'tel'    => $inter->getTelPortable(),
                'charge' => count($this->proposerRepo->findFamilleIdsPrestByIntervenant($num, 'MENA')),
                'proche' => $proche,
            ];
        }

        usort($out, fn ($a, $b) => ($b['proche'] <=> $a['proche'])
            ?: ($a['charge'] <=> $b['charge'])
            ?: strcmp($a['nom'], $b['nom']));

        return $out;
    }
}
