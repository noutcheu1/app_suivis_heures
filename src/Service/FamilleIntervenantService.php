<?php

namespace App\Service;

use App\Entity\Principal\Famille;
use App\Entity\Principal\Intervenant;
use App\Entity\Principal\Proposer;
use App\Repository\FamilleRepository;
use App\Repository\IntervenantRepository;
use App\Repository\ProposerRepository;

/**
 * Service métier pour les relations Famille ↔ Intervenant.
 *
 * Centralise toutes les questions :
 *   - Quelles familles suit cet intervenant ?
 *   - Quels intervenants sont assignés à cette famille ?
 *   - Cet intervenant peut-il pointer chez cette famille ? (QR scan)
 *   - Quel est le prochain créneau de l'intervenant ?
 *   - Planning hebdomadaire organisé par jour
 */
class FamilleIntervenantService
{
    public function __construct(
        private ProposerRepository    $proposerRepository,
        private FamilleRepository     $familleRepository,
        private IntervenantRepository $intervenantRepository,
    ) {}

    // ── Intervenants d'une famille ────────────────────────────────────────────

    /**
     * Retourne les entités Intervenant actives assignées à la famille.
     *
     * @return Intervenant[]
     */
    public function getIntervenantsForFamille(string $numeroFamille): array
    {
        $ids = $this->proposerRepository->findNumsSalarieByFamille($numeroFamille);

        return array_values(array_filter(
            array_map(fn(int $id) => $this->intervenantRepository->find($id), $ids)
        ));
    }

    // ── Familles d'un intervenant ─────────────────────────────────────────────

    /**
     * Retourne les entités Famille actives assignées à l'intervenant.
     *
     * @return Famille[]
     */
    public function getFamillesForIntervenant(int $numSalarie): array
    {
        $numeros = $this->proposerRepository->findNumerosFamilleByIntervenant($numSalarie);

        return array_values(array_filter(
            array_map(fn(string $n) => $this->familleRepository->findByNumero($n), $numeros)
        ));
    }

    // ── Assignations (entités Proposer) ──────────────────────────────────────

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

    // ── Validation QR scan ────────────────────────────────────────────────────

    /**
     * Vérifie qu'un intervenant est autorisé à pointer chez une famille.
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
     * Planning organisé par jour, enrichi avec les entités Famille.
     *
     * @return array<string, array{proposer: Proposer, famille: ?Famille}[]>
     */
    public function getPlanningHebdo(int $numSalarie): array
    {
        $jours   = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
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
}
