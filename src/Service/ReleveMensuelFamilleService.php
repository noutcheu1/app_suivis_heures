<?php

namespace App\Service;

use App\Entity\Horaire\Relevemensuelfam;
use App\Entity\Horaire\TarifFamille;
use App\Repository\RelevemensuelfamRepository;
use App\Repository\TarifFamilleRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReleveMensuelFamilleService
{
    public function __construct(
        private TarifFamilleRepository    $tarifFamilleRepository,
        private RelevemensuelfamRepository $releveRepository,
        private EntityManagerInterface    $entityManager,
    ) {}

    // ── Taux horaire par famille ──────────────────────────────────────────────

    /**
     * Taux horaire actif pour une famille et un type de prestation.
     * Retourne null si aucun tarif spécifique n'est défini (→ utiliser tarif global).
     */
    public function getTarifActifFamille(string $numFam, string $typePresta, ?string $moisAnnee = null): ?TarifFamille
    {
        return $this->tarifFamilleRepository->findActif($numFam, $typePresta, $moisAnnee);
    }

    /**
     * Historique complet des tarifs d'une famille.
     */
    public function getTarifsFamille(string $numFam): array
    {
        return $this->tarifFamilleRepository->findByFamille($numFam);
    }

    /**
     * Tous les tarifs famille actifs (vue globale admin).
     */
    public function getTousLesTarifsActifs(): array
    {
        return $this->tarifFamilleRepository->findTousActifs();
    }

    /**
     * Crée une nouvelle version du taux horaire pour une famille.
     * Ne modifie jamais une entrée existante — toujours une nouvelle ligne horodatée.
     */
    public function creerTarifFamille(string $numFam, string $typePresta, float $taux, string $dateDebut): TarifFamille
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $dateDebut)) {
            $dateDebut = date('Y-m');
        }

        $tarif = new TarifFamille();
        $tarif->setNumFam($numFam);
        $tarif->setTypePresta($typePresta);
        $tarif->setTauxHoraire(number_format($taux, 2, '.', ''));
        $tarif->setDateDebut($dateDebut);

        $this->entityManager->persist($tarif);
        $this->entityManager->flush();

        return $tarif;
    }

    /**
     * Crée ou met à jour le questionnaire de satisfaction + infos de règlement
     * pour un ou plusieurs types de prestation d'un même mois.
     *
     * @param string[] $prestTypes  Ex. ['ENFA'], ['MENA'] ou ['ENFA','MENA']
     * @param array    $donnees     Clés : avis*, typeReglement, numCheque, nbrCESU,
     *                              montantPrincipal, complementCESU, montantComplement, signer (bool)
     */
    public function sauvegarderQuestionnaire(
        string $numFam,
        string $moisAnnee,
        array  $prestTypes,
        array  $donnees
    ): void {
        foreach ($prestTypes as $typePresta) {
            $releve = $this->releveRepository->findByMoisAnneeFamille($moisAnnee, $numFam, $typePresta);

            if (!$releve) {
                $releve = new Relevemensuelfam();
                $releve->setNumFam($numFam);
                $releve->setMoisannee($moisAnnee);
                $releve->setTypePresta($typePresta);
                $this->entityManager->persist($releve);
            }

            if (isset($donnees['avisPonctualite'])) {
                $releve->setAvisPonctualite($donnees['avisPonctualite'] ? (int) $donnees['avisPonctualite'] : null);
            }
            if (isset($donnees['avisReguRela'])) {
                $releve->setAvisReguRela($donnees['avisReguRela'] ? (int) $donnees['avisReguRela'] : null);
            }
            if (isset($donnees['avisRespectHo'])) {
                $releve->setAvisRespectHo($donnees['avisRespectHo'] ? (int) $donnees['avisRespectHo'] : null);
            }
            if (isset($donnees['avisQualiteTr'])) {
                $releve->setAvisQualiteTr($donnees['avisQualiteTr'] ? (int) $donnees['avisQualiteTr'] : null);
            }
            if (array_key_exists('typeReglement', $donnees)) {
                $releve->setTypeReglement($donnees['typeReglement'] ?: null);
            }
            if (array_key_exists('numCheque', $donnees)) {
                $releve->setNumCheque($donnees['numCheque'] ?: null);
            }
            if (array_key_exists('nbrCESU', $donnees)) {
                $releve->setNbrCESU($donnees['nbrCESU'] !== '' && $donnees['nbrCESU'] !== null ? (int) $donnees['nbrCESU'] : null);
            }
            if (array_key_exists('montantPrincipal', $donnees)) {
                $releve->setMontantPrincipal($donnees['montantPrincipal'] ?: null);
            }
            if (array_key_exists('complementCESU', $donnees)) {
                $releve->setComplementCESU($donnees['complementCESU'] ?: null);
            }
            if (array_key_exists('montantComplement', $donnees)) {
                $releve->setMontantComplement($donnees['montantComplement'] ?: null);
            }

            if (!empty($donnees['signer']) && !$releve->getSignerLe()) {
                $releve->setSignerLe(new \DateTime());
            }
        }

        $this->entityManager->flush();
    }

    // ── Historique des relevés par famille ───────────────────────────────────

    /**
     * Historique complet des relevés d'une famille, groupés par mois (DESC).
     *
     * @return array<int, array{moisannee: string, releves: Relevemensuelfam[], signe: bool}>
     */
    public function getHistoriqueReleves(string $numFam): array
    {
        $tous  = $this->releveRepository->findByFamille($numFam);
        $byMois = [];

        foreach ($tous as $r) {
            $m = $r->getMoisannee();
            if (!isset($byMois[$m])) {
                $byMois[$m] = ['moisannee' => $m, 'releves' => [], 'signe' => false];
            }
            $byMois[$m]['releves'][] = $r;
            if ($r->getSignerLe()) {
                $byMois[$m]['signe'] = true;
            }
        }

        krsort($byMois);
        return array_values($byMois);
    }

    // ── Exceptions de facturation ─────────────────────────────────────────────

    /**
     * Toutes les exceptions de facturation (vue globale admin).
     */
    public function getToutesLesExceptions(): array
    {
        return $this->releveRepository->findAvecException();
    }

    /**
     * Exceptions d'une famille spécifique.
     */
    public function getExceptionsFamille(string $numFam): array
    {
        return $this->releveRepository->findExceptionsByFamille($numFam);
    }

    /**
     * Ajoute ou modifie l'exception de facturation d'un mois.
     * Crée le relevé mensuel s'il n'existe pas encore.
     */
    public function ajouterOuModifierException(
        string $numFam,
        string $moisAnnee,
        string $typePresta,
        string $libele,
        float  $montant
    ): Relevemensuelfam {
        $releve = $this->releveRepository->findByMoisAnneeFamille($moisAnnee, $numFam, $typePresta);

        if (!$releve) {
            $releve = new Relevemensuelfam();
            $releve->setNumFam($numFam);
            $releve->setMoisannee($moisAnnee);
            $releve->setTypePresta($typePresta);
            $this->entityManager->persist($releve);
        }

        $releve->setLibelerSupl(trim($libele));
        $releve->setMontantSupl(number_format($montant, 2, '.', ''));

        $this->entityManager->flush();

        return $releve;
    }

    /**
     * Supprime l'exception de facturation d'un mois (met les champs à null).
     * Ne supprime pas la ligne pour préserver les autres données du relevé (avis, signature…).
     */
    public function supprimerException(string $numFam, string $moisAnnee, string $typePresta): void
    {
        $releve = $this->releveRepository->findByMoisAnneeFamille($moisAnnee, $numFam, $typePresta);

        if ($releve) {
            $releve->setLibelerSupl(null);
            $releve->setMontantSupl(null);
            $this->entityManager->flush();
        }
    }
}
