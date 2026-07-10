<?php

namespace App\Service;

use App\Entity\Horaire\Conge;
use App\Repository\CongeRepository;

/**
 * Gestion des congés (donnée métier permanente, indépendante des campagnes).
 *
 * Déclaration libre depuis les espaces famille / intervenant : créer, modifier,
 * annuler (suppression douce), avec validation des dates et anti-chevauchement.
 */
class CongeService
{
    public function __construct(private CongeRepository $repo) {}

    /** @return Conge[] Congés actifs de la personne, du plus récent au plus ancien. */
    public function listerPour(string $typePersonne, string $personneId): array
    {
        return $this->repo->findActifsByPersonne($typePersonne, $personneId);
    }

    /**
     * Crée un congé LIBRE. Retourne ['ok'=>bool, 'erreur'=>?string, 'conge'=>?Conge].
     *
     * @param array<string,mixed> $attributs maintienPrestation|souhaiteRemplacement|disponibleRemplacement
     */
    public function ajouter(string $typePersonne, string $personneId, ?string $debut, ?string $fin, ?string $motif, array $attributs = []): array
    {
        $d = $this->toDate($debut);
        $f = $this->toDate($fin);
        if ($erreur = $this->validerPeriode($d, $f, $typePersonne, $personneId, null)) {
            return ['ok' => false, 'erreur' => $erreur, 'conge' => null];
        }

        $conge = (new Conge())
            ->setTypePersonne($typePersonne)
            ->setPersonneId($personneId)
            ->setDateDebut($d)
            ->setDateFin($f)
            ->setMotif($this->nettoyer($motif))
            ->setOrigine(Conge::ORIGINE_LIBRE)
            // Les familles n'ont pas besoin de validation ; les intervenants passent
            // par une validation admin.
            ->setStatut($typePersonne === Conge::PERSONNE_INTERVENANT ? Conge::STATUT_EN_ATTENTE : Conge::STATUT_VALIDE);
        $this->appliquerAttributs($conge, $attributs);

        $this->repo->save($conge);

        return ['ok' => true, 'erreur' => null, 'conge' => $conge];
    }

    /** Modifie un congé existant appartenant à la personne. */
    public function modifier(int $id, string $typePersonne, string $personneId, ?string $debut, ?string $fin, ?string $motif, array $attributs = [], bool $parAdmin = false): array
    {
        $conge = $this->repo->findUnActifPour($id, $typePersonne, $personneId);
        if (!$conge) {
            return ['ok' => false, 'erreur' => 'Congé introuvable.', 'conge' => null];
        }
        // Un congé intervenant VALIDÉ est verrouillé pour l'intervenant (admin seul).
        if (!$parAdmin && $typePersonne === Conge::PERSONNE_INTERVENANT && $conge->estValide()) {
            return ['ok' => false, 'erreur' => 'Ce congé a été validé : contactez l\'administrateur pour le modifier.', 'conge' => null];
        }

        $d = $this->toDate($debut);
        $f = $this->toDate($fin);
        if ($erreur = $this->validerPeriode($d, $f, $typePersonne, $personneId, $id)) {
            return ['ok' => false, 'erreur' => $erreur, 'conge' => null];
        }

        $conge->setDateDebut($d)->setDateFin($f)->setMotif($this->nettoyer($motif))->toucher();
        $this->appliquerAttributs($conge, $attributs);
        // Un intervenant qui modifie son congé le repasse EN_ATTENTE de validation
        // (une modif admin conserve le statut).
        if (!$parAdmin && $typePersonne === Conge::PERSONNE_INTERVENANT) {
            $conge->setStatut(Conge::STATUT_EN_ATTENTE);
        }
        $this->repo->save($conge);

        return ['ok' => true, 'erreur' => null, 'conge' => $conge];
    }

    /** Validation / refus d'un congé par l'admin. */
    public function valider(int $id): bool
    {
        return $this->changerStatut($id, Conge::STATUT_VALIDE);
    }

    public function refuser(int $id): bool
    {
        return $this->changerStatut($id, Conge::STATUT_REFUSE);
    }

    /** Déverrouille un congé validé : le repasse en attente (l'admin reprend la main). */
    public function remettreEnAttente(int $id): bool
    {
        return $this->changerStatut($id, Conge::STATUT_EN_ATTENTE);
    }

    private function changerStatut(int $id, string $statut): bool
    {
        $conge = $this->repo->find($id);
        if (!$conge || $conge->estAnnule()) {
            return false;
        }
        $conge->setStatut($statut)->toucher();
        $this->repo->save($conge);
        return true;
    }

    /** Annule (suppression douce) un congé appartenant à la personne. */
    public function annuler(int $id, string $typePersonne, string $personneId, bool $parAdmin = false): bool
    {
        $conge = $this->repo->findUnActifPour($id, $typePersonne, $personneId);
        if (!$conge) {
            return false;
        }
        // Un congé intervenant VALIDÉ ne peut être supprimé que par l'admin.
        if (!$parAdmin && $typePersonne === Conge::PERSONNE_INTERVENANT && $conge->estValide()) {
            return false;
        }
        $conge->setAnnuleLe(new \DateTimeImmutable())->toucher();
        $this->repo->save($conge);
        return true;
    }

    // ── Interne ───────────────────────────────────────────────────────────────

    private function validerPeriode(?\DateTimeImmutable $d, ?\DateTimeImmutable $f, string $type, string $personneId, ?int $exclureId): ?string
    {
        if (!$d || !$f) {
            return 'Merci d\'indiquer les deux dates (début et fin).';
        }
        if ($d > $f) {
            return 'La date de fin doit être après la date de début.';
        }
        // Un congé refusé ne bloque pas une nouvelle déclaration.
        if (!empty($this->repo->findChevauchant($type, $personneId, $d, $f, $exclureId, [Conge::STATUT_EN_ATTENTE, Conge::STATUT_VALIDE]))) {
            return 'Vous avez déjà un congé déclaré sur cette période.';
        }
        return null;
    }

    private function appliquerAttributs(Conge $conge, array $a): void
    {
        if (array_key_exists('maintienPrestation', $a)) {
            $conge->setMaintienPrestation($a['maintienPrestation']);
        }
        if (array_key_exists('souhaiteRemplacement', $a)) {
            $conge->setSouhaiteRemplacement($a['souhaiteRemplacement']);
        }
        if (array_key_exists('disponibleRemplacement', $a)) {
            $conge->setDisponibleRemplacement($a['disponibleRemplacement']);
        }
    }

    private function toDate(?string $s): ?\DateTimeImmutable
    {
        $s = trim((string) $s);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return null;
        }
        try { return new \DateTimeImmutable($s); } catch (\Exception) { return null; }
    }

    private function nettoyer(?string $s): ?string
    {
        $s = trim(strip_tags((string) $s));
        return $s === '' ? null : mb_substr($s, 0, 500);
    }
}
