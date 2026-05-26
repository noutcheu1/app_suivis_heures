<?php

namespace App\Service;

use App\Entity\Horaire\Horaireinter;
use App\Entity\Horaire\Tarif;
use App\Repository\HoraireinterRepository;
use App\Repository\RelevemensuelfamRepository;
use App\Repository\TarifFamilleRepository;
use App\Repository\TarifRepository;

/**
 * Calcule la facture mensuelle d'une famille.
 *
 * Logique :
 *   1. Récupère toutes les prestations actives du mois.
 *   2. Calcule les heures par type (ENFA / MENA).
 *   3. Applique les paliers tarifaires du Tarif global (alheureGE / alheureM).
 *      Si le palier renvoie "V" → cherche le taux spécifique TarifFamille.
 *   4. Ajoute km × tarif_km, frais de gestion, abonnement.
 *   5. Ajoute l'exception du mois (montantSupl de Relevemensuelfam).
 */
class FactureService
{
    public function __construct(
        private HoraireinterRepository    $horaireRepo,
        private TarifRepository           $tarifRepo,
        private TarifFamilleRepository    $tarifFamilleRepo,
        private RelevemensuelfamRepository $releveRepo,
    ) {}

    /**
     * Calcule et retourne toutes les données de la facture pour une famille/mois.
     *
     * @param string $moisAnnee Format YYYY-MM
     * @return array{
     *   numFam: string, moisAnnee: string,
     *   prestationsGE: Horaireinter[], prestationsMENA: Horaireinter[],
     *   heuresGE: float, heuresMENA: float,
     *   tauxGE: float, tauxMENA: float,
     *   tauxGESource: string, tauxMENASource: string,
     *   montantGE: float, montantMENA: float,
     *   nbInterventions: int, fraisGestion: float,
     *   kmTotal: float, montantKm: float,
     *   abonnement: float, montantSupl: float, libeleSupl: string,
     *   montantTotal: float, tarif: ?Tarif,
     *   releveGE: mixed, releveMENA: mixed
     * }
     */
    public function calculerFacture(string $numFam, string $moisAnnee): array
    {
        $prestations = $this->horaireRepo->findByFamilleEtMois($numFam, $moisAnnee);

        // Accepte mm/YYYY (format français) ou YYYY-MM (ISO)
        if (str_contains($moisAnnee, '/')) {
            $dateRef = \DateTime::createFromFormat('m/Y', $moisAnnee) ?: new \DateTime();
        } else {
            $dateRef = \DateTime::createFromFormat('Y-m-d', $moisAnnee . '-01') ?: new \DateTime();
        }
        $tarif   = $this->tarifRepo->findActif($dateRef);

        $prestGE   = array_values(array_filter($prestations, fn($p) => $p->getTypePresta() === 'ENFA'));
        $prestMENA = array_values(array_filter($prestations, fn($p) => $p->getTypePresta() === 'MENA'));

        $heuresGE   = array_sum(array_map(fn($p) => $this->heures($p), $prestGE));
        $heuresMENA = array_sum(array_map(fn($p) => $this->heures($p), $prestMENA));
        $kmTotal    = array_sum(array_map(fn($p) => (float)($p->getKmAvecEnfant() ?? 0), $prestGE));

        [$tauxGE,   $tauxGESource]   = $this->resoudreTaux($numFam, 'GE', $heuresGE,   $moisAnnee, $tarif);
        [$tauxMENA, $tauxMENASource] = $this->resoudreTaux($numFam, 'M',  $heuresMENA, $moisAnnee, $tarif);

        $montantGE   = round($heuresGE   * $tauxGE,   2);
        $montantMENA = round($heuresMENA * $tauxMENA, 2);

        $nbInter        = count($prestations);
        $parInter       = (float)($tarif?->getParIntervention()    ?? 0);
        $maxInter       = (float)($tarif?->getMaxParIntervention() ?? 0);
        $fraisGestion   = round(min($nbInter * $parInter, $maxInter > 0 ? $maxInter : PHP_FLOAT_MAX), 2);

        $montantKm  = round($kmTotal * (float)($tarif?->getKmEnfants() ?? 0), 2);
        $abonnement = round((float)($tarif?->getAbonnement() ?? 0), 2);

        $releveGE   = $this->releveRepo->findByMoisAnneeFamille($moisAnnee, $numFam, 'ENFA');
        $releveMENA = $this->releveRepo->findByMoisAnneeFamille($moisAnnee, $numFam, 'MENA');

        $montantSupl = (float)($releveGE?->getMontantSupl()   ?? 0)
                     + (float)($releveMENA?->getMontantSupl() ?? 0);
        $libeleSupl  = implode(' / ', array_filter([
            $releveGE?->getLibelerSupl(),
            $releveMENA?->getLibelerSupl(),
        ]));

        $montantTotal = round($montantGE + $montantMENA + $fraisGestion + $montantKm + $abonnement + $montantSupl, 2);

        return [
            'numFam'          => $numFam,
            'moisAnnee'       => $moisAnnee,
            'prestationsGE'   => $prestGE,
            'prestationsMENA' => $prestMENA,
            'heuresGE'        => round($heuresGE,   2),
            'heuresMENA'      => round($heuresMENA, 2),
            'tauxGE'          => $tauxGE,
            'tauxMENA'        => $tauxMENA,
            'tauxGESource'    => $tauxGESource,
            'tauxMENASource'  => $tauxMENASource,
            'montantGE'       => $montantGE,
            'montantMENA'     => $montantMENA,
            'nbInterventions' => $nbInter,
            'fraisGestion'    => $fraisGestion,
            'kmTotal'         => round($kmTotal, 1),
            'montantKm'       => $montantKm,
            'abonnement'      => $abonnement,
            'montantSupl'     => $montantSupl,
            'libeleSupl'      => $libeleSupl,
            'montantTotal'    => $montantTotal,
            'tarif'           => $tarif,
            'releveGE'        => $releveGE,
            'releveMENA'      => $releveMENA,
        ];
    }

    // ── Résolution du taux horaire ────────────────────────────────────────────

    /**
     * Retourne [taux, source] pour un type de prestation.
     * source = 'famille' | 'global' | 'V_non_défini' | 'aucun'
     *
     * @return array{float, string}
     */
    private function resoudreTaux(string $numFam, string $type, float $heures, string $moisAnnee, ?Tarif $tarif): array
    {
        if ($tarif === null) {
            return [0.0, 'aucun'];
        }

        $json = $type === 'GE' ? $tarif->getAlheureGe() : $tarif->getAlheureM();
        if (!$json) {
            return [0.0, 'aucun'];
        }

        $regles = json_decode($json, true) ?? [];
        $valeur = $this->appliquerRegle($regles, $heures);

        if ($valeur === 'V') {
            $tarifFam = $this->tarifFamilleRepo->findActif($numFam, $type, $moisAnnee);
            if ($tarifFam) {
                return [(float)$tarifFam->getTauxHoraire(), 'famille'];
            }
            return [0.0, 'V_non_défini'];
        }

        return [(float)$valeur, 'global'];
    }

    // ── Évaluation des paliers tarifaires ─────────────────────────────────────

    /** @return float|string */
    private function appliquerRegle(array $regles, float $h): mixed
    {
        foreach ($regles as [$condition, $valeur]) {
            if ($this->evalCondition($condition, $h)) {
                return is_string($valeur) ? $valeur : (float)$valeur;
            }
        }
        return 0.0;
    }

    /**
     * Évalue une condition JSON du type "$h >= 16" ou "$h < 16 && $h >= 8"
     * sans recourir à eval().
     */
    private function evalCondition(string $condition, float $h): bool
    {
        $expr = str_replace('$h', number_format($h, 10, '.', ''), $condition);

        foreach (explode('&&', $expr) as $part) {
            if (!preg_match('/^\s*([\d.]+)\s*(>=|<=|>|<)\s*([\d.]+)\s*$/', trim($part), $m)) {
                continue;
            }
            $ok = match ($m[2]) {
                '>=' => (float)$m[1] >= (float)$m[3],
                '<=' => (float)$m[1] <= (float)$m[3],
                '>'  => (float)$m[1] >  (float)$m[3],
                '<'  => (float)$m[1] <  (float)$m[3],
            };
            if (!$ok) {
                return false;
            }
        }
        return true;
    }

    // ── Calcul des heures depuis l'entité ─────────────────────────────────────

    /**
     * Heures facturables : admin choisit 'fam' → heures famille, sinon heures intervenant.
     */
    private function heures(Horaireinter $h): float
    {
        $debut = $h->getHeureDebutPresta();
        $fin   = $h->getHeureFinPresta();

        if (!$debut || !$fin) {
            return 0.0;
        }
        $sec = (int)$fin->format('H') * 3600 + (int)$fin->format('i') * 60
             - (int)$debut->format('H') * 3600 - (int)$debut->format('i') * 60;
        if ($sec < 0) {
            $sec += 86400;
        }
        return round($sec / 3600, 4);
    }
}
