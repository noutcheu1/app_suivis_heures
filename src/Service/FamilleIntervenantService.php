<?php

namespace App\Service;

use App\Entity\Horaire\Horaireinter;
use App\Entity\Principal\Famille;
use App\Entity\Principal\Intervenant;
use App\Entity\Principal\Proposer;
use App\Repository\FamilleRepository;
use App\Repository\HoraireinterRepository;
use App\Repository\IntervenantRepository;
use App\Repository\ProposerRepository;

/**
 * Service métier Famille ↔ Intervenant.
 *
 * Règle fondamentale : on ne travaille QU'avec les familles et les intervenants
 * qui ont une assignation active dans `proposer`
 * (dateFin IS NULL, ou "0000-00-00", ou >= aujourd'hui).
 */
class FamilleIntervenantService
{
    public function __construct(
        private ProposerRepository    $proposerRepository,
        private FamilleRepository     $familleRepository,
        private IntervenantRepository $intervenantRepository,
        private HoraireinterRepository $horaireRepository,
    ) {}

    // ── Listes globales (filtrées sur le planning actif) ──────────────────────

    /**
     * Toutes les familles ayant un planning actif dans proposer.
     *
     * @return Famille[]
     */
    public function getFamillesActives(): array
    {
        return $this->familleRepository->findWithActivePlanning();
    }

    /**
     * Tous les intervenants ayant un planning actif dans proposer.
     *
     * @return Intervenant[]
     */
    public function getIntervenantsActifs(): array
    {
        return $this->intervenantRepository->findWithActivePlanning();
    }

    // ── Relations croisées ────────────────────────────────────────────────────

    /**
     * Familles actives assignées à un intervenant.
     *
     * @return Famille[]
     */
    public function getFamillesForIntervenant(int $numSalarie): array
    {
        return $this->familleRepository->findByIntervenantActif($numSalarie);
    }

    /**
     * Intervenants actifs assignés à une famille.
     *
     * @return Intervenant[]
     */
    public function getIntervenantsForFamille(string $numeroFamille): array
    {
        return $this->intervenantRepository->findByFamilleActif($numeroFamille);
    }

    // ── Assignations brutes (entités Proposer) ────────────────────────────────

    /**
     * Assignations actives de l'intervenant (pour le planning).
     *
     * @return Proposer[]
     */
    public function getAssignationsActives(int $numSalarie): array
    {
        return $this->proposerRepository->findActivesByIntervenant($numSalarie);
    }

    /**
     * Assignations actives de la famille (pour son tableau de bord).
     *
     * @return Proposer[]
     */
    public function getAssignationsFamille(string $numeroFamille): array
    {
        return $this->proposerRepository->findActivesByFamille($numeroFamille);
    }

    // ── Pré-remplissage formulaire ────────────────────────────────────────────

    /**
     * Retourne les données de pré-remplissage pour un proposer identifié
     * par sa clé composite encodée. Retourne null si introuvable.
     *
     * @return array{famille: string, type: string, heureDebut: string, heureFin: ?string}|null
     */
    public function getProposerPrefill(int $numSalarie, string $numFam, string $type, string $typeAdh, string $jour, string $hDebHHii): ?array
    {
        $p = $this->proposerRepository->findOneByKey($numSalarie, $numFam, $type, $typeAdh, $jour, $hDebHHii);
        if (!$p) {
            return null;
        }

        return [
            'famille'    => $p->getNumeroFamille(),
            'type'       => $p->getTypePrestation(),
            'heureDebut' => $p->getHeureDebut()->format('H:i'),
            'heureFin'   => $p->getHeureFin()?->format('H:i'),
        ];
    }

    // ── Validation QR scan ────────────────────────────────────────────────────

    /**
     * Vérifie qu'un intervenant est autorisé à pointer chez une famille.
     * L'intervenant doit être actif dans proposer pour cette famille.
     */
    public function peutPointer(int $numSalarie, string $numeroFamille): bool
    {
        return $this->proposerRepository->isIntervenantAssignedToFamille($numSalarie, $numeroFamille);
    }

    // ── Dashboard intervenant ─────────────────────────────────────────────────

    /**
     * Prochain créneau de l'intervenant avec l'entité Famille correspondante.
     *
     * @return array{assignation: Proposer, famille: ?Famille}|null
     */
    public function getProchainCreneau(int $numSalarie): ?array
    {
        $slot = $this->proposerRepository->findNextSlot($numSalarie);
        if ($slot === null) {
            return null;
        }

        return [
            'assignation' => $slot,
            'famille'     => $this->familleRepository->findByNumero($slot->getNumeroFamille()),
        ];
    }

    // ── Planning hebdomadaire ─────────────────────────────────────────────────

    /**
     * Planning organisé par jour avec entités Famille.
     * Seules les assignations actives (dans proposer) sont incluses.
     *
     * @return array<string, array{proposer: Proposer, famille: ?Famille}[]>
     */
    public function getPlanningHebdo(int $numSalarie): array
    {
        $jours    = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $planning = array_fill_keys($jours, []);

        foreach ($this->proposerRepository->findActivesByIntervenant($numSalarie) as $proposer) {
            $jour = mb_strtolower($proposer->getJour());
            if (!isset($planning[$jour])) {
                continue;
            }
            $planning[$jour][] = [
                'proposer' => $proposer,
                'famille'  => $this->familleRepository->findByNumero($proposer->getNumeroFamille()),
            ];
        }

        return $planning;
    }

    /**
     * Planning sur une plage de dates arbitraire (ex. 25 mois M-1 → 24 mois M).
     *
     * La plage peut chevaucher deux mois calendaires.
     * - Dates <= aujourd'hui : prestations réelles (confirmed) depuis horaireinter.
     *   Si aucun confirmé n'existe pour ce couple (date, famille), le créneau planifié
     *   est quand même affiché afin de permettre la saisie rétroactive.
     * - Dates > aujourd'hui  : planification proposer uniquement (planned).
     *
     * La vérification "remplacement" est faite par couple (date + famille) :
     * un confirmé pour la famille A ne masque pas un planifié pour la famille B.
     *
     * @return array<string, array{date: \DateTime, type: string, horaire: ?Horaireinter, proposer: ?Proposer, famille: ?Famille}[]>
     */
    public function getPlanningMensuel(\DateTime $firstDay, \DateTime $lastDay, int $numSalarie): array
    {
        $byDate = [];
        $today  = new \DateTime('today');
        $cutoff = $lastDay > $today ? $today : clone $lastDay;

        // 1. Prestations réelles pour la plage [firstDay, cutoff]
        if ($firstDay <= $cutoff) {
            $months = array_unique([
                $firstDay->format('Y-m'),
                $cutoff->format('Y-m'),
            ]);
            foreach ($months as $ym) {
                [$y, $m] = array_map('intval', explode('-', $ym));
                foreach ($this->horaireRepository->findByIntervenantForMonth($numSalarie, $y, $m) as $h) {
                    $date = $h->getDatePresta();
                    if (!$date || $date < $firstDay || $date > $cutoff) {
                        continue;
                    }
                    $key    = $date->format('Y-m-d');
                    $numFam = $h->getNumFam();
                    $byDate[$key][] = [
                        'date'     => clone $date,
                        'type'     => 'confirmed',
                        'horaire'  => $h,
                        'proposer' => null,
                        'famille'  => $numFam !== null ? $this->familleRepository->findByNumero($numFam) : null,
                    ];
                }
            }
        }

        // 2. Propositions planifiées pour toute la plage
        //    Vérification par (date + famille) : un confirmé pour la famille A
        //    ne masque pas un planifié pour la famille B le même jour.
        $planned = $this->proposerRepository->expandForPeriodIntervenant($numSalarie, $firstDay, $lastDay);

        foreach ($planned as $item) {
            $date    = $item['date'];
            $key     = $date->format('Y-m-d');
            $numFamP = $item['proposer']->getNumeroFamille();

            // Confirmé pour cette même famille ET ce même type de prestation sur cette même date ?
            $typeP = $item['proposer']->getTypePrestation();
            $hasConfirmedForFam = false;
            if (isset($byDate[$key])) {
                foreach ($byDate[$key] as $existing) {
                    if ($existing['type'] === 'confirmed'
                        && $existing['horaire'] !== null
                        && trim((string)$existing['horaire']->getNumFam()) === trim($numFamP)
                        && $existing['horaire']->getTypePresta() === $typeP) {
                        $hasConfirmedForFam = true;
                        break;
                    }
                }
            }

            if (!$hasConfirmedForFam) {
                $byDate[$key][] = [
                    'date'     => $date,
                    'type'     => 'planned',
                    'horaire'  => null,
                    'proposer' => $item['proposer'],
                    'famille'  => ($numFamP && $numFamP !== '') ? $this->familleRepository->findByNumero($numFamP) : null,
                ];
            }
        }

        ksort($byDate);
        return $byDate;
    }
}
