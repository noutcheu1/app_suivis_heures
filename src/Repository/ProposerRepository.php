<?php

namespace App\Repository;

use App\Entity\Principal\Proposer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Proposer>
 *
 * Règle "actif" dans proposer :
 *   dateFin_Proposer IS NULL
 *   OR dateFin_Proposer = "0000-00-00"   (valeur vide MySQL, = pas de fin définie)
 *   OR dateFin_Proposer >= CURDATE()
 *
 * On utilise DBAL pour les requêtes qui touchent à dateFin afin d'éviter les
 * problèmes d'hydratation Doctrine sur les dates MySQL "0000-00-00".
 */
class ProposerRepository extends ServiceEntityRepository
{
    private Connection $conn;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Proposer::class);
        /** @var Connection $conn */
        $this->conn = $registry->getConnection('principal');
    }

    // ── Condition SQL partagée ────────────────────────────────────────────────

    private const ACTIF_SQL =
        "((p.idPresta_Prestations = 'MENA' OR p.idPresta_Prestations = 'ENFA')
          AND p.idADH_TypeADH = 'PREST'
          AND (p.dateFin_Proposer IS NULL
               OR p.dateFin_Proposer = '0000-00-00'
               OR p.dateFin_Proposer >= CURDATE()))";

    /**
     * Types de prestation (MENA/ENFA) par famille pour un intervenant, depuis TOUS
     * ses proposers PREST (actifs ou expirés). Sert à filtrer le select de saisie
     * par type de façon fiable (cohérent avec findByIntervenantActif).
     *
     * @return array<string, string[]> ['numFam' => ['MENA', 'ENFA'], ...]
     */
    public function findTypesParFamilleForIntervenant(int $numSalarie): array
    {
        $rows = $this->conn->fetchAllAssociative(
            "SELECT DISTINCT p.numero_Famille AS numFam, p.idPresta_Prestations AS type
             FROM proposer p
             INNER JOIN famille f ON f.numero_Famille = p.numero_Famille
             WHERE p.numSalarie_Intervenants = ?
               AND p.idADH_TypeADH = 'PREST'
               AND (p.idPresta_Prestations = 'MENA' OR p.idPresta_Prestations = 'ENFA')
               AND (f.archive_Famille = 0 OR f.archive_Famille IS NULL)
               AND f.numero_Famille != 9999
               AND (f.mand_Famille = 0 OR f.mand_Famille IS NULL)
               AND ((f.PGE_Famille IS NOT NULL AND f.PGE_Famille != '')
                    OR (f.PM_Famille IS NOT NULL AND f.PM_Famille != ''))",
            [$numSalarie]
        );

        $map = [];
        foreach ($rows as $r) {
            $map[(string) $r['numFam']][] = strtoupper((string) $r['type']);
        }
        return $map;
    }

    // ── Requêtes retournant des entités Proposer ──────────────────────────────

    /**
     * Retourne les numéros de famille (actifs ET expirés) où l'intervenant a un planning
     * idADH_TypeADH = 'PREST' pour le type donné (MENA ou ENFA).
     * Si $type est null, retourne pour les deux types.
     * Utilisé pour filtrer les relevés : seules ces familles peuvent apparaître.
     *
     * @return string[]
     */
    public function findFamilleIdsPrestByIntervenant(int $numSalarie, ?string $type = null): array
    {
        if ($type !== null) {
            return $this->conn->fetchFirstColumn(
                "SELECT DISTINCT p.numero_Famille
                 FROM proposer p
                 WHERE p.numSalarie_Intervenants = ?
                   AND p.idADH_TypeADH = 'PREST'
                   AND p.idPresta_Prestations = ?",
                [$numSalarie, strtoupper($type)]
            );
        }

        return $this->conn->fetchFirstColumn(
            "SELECT DISTINCT p.numero_Famille
             FROM proposer p
             WHERE p.numSalarie_Intervenants = ?
               AND p.idADH_TypeADH = 'PREST'
               AND (p.idPresta_Prestations = 'MENA' OR p.idPresta_Prestations = 'ENFA')",
            [$numSalarie]
        );
    }

    /**
     * Assignations actives d'un intervenant.
     *
     * @return Proposer[]
     */
    public function findActivesByIntervenant(int $numSalarie): array
    {
        $rows = $this->conn->fetchAllAssociative(
            'SELECT * FROM proposer p
             WHERE p.numSalarie_Intervenants = ?
               AND ' . self::ACTIF_SQL . '
             ORDER BY p.jour_Proposer, p.hDeb_Proposer',
            [$numSalarie]
        );

        return $this->hydrateRows($rows);
    }

    /**
     * Assignations actives d'une famille.
     *
     * @return Proposer[]
     */
    public function findActivesByFamille(string $numeroFamille): array
    {
        $rows = $this->conn->fetchAllAssociative(
            'SELECT p.* FROM proposer p
             INNER JOIN intervenants i ON i.numSalarie_Intervenants = p.numSalarie_Intervenants
             WHERE p.numero_Famille = ?
               AND ' . self::ACTIF_SQL . '
               AND (i.archive_Intervenants = 0 OR i.archive_Intervenants IS NULL)
               AND (i.dateEntree_Intervenants IS NULL OR i.dateEntree_Intervenants <= CURDATE())
               AND (i.dateSortie_Intervenants IS NULL OR i.dateSortie_Intervenants = "0000-00-00" OR i.dateSortie_Intervenants >= CURDATE())
             ORDER BY p.jour_Proposer, p.hDeb_Proposer',
            [$numeroFamille]
        );

        return $this->hydrateRows($rows);
    }

    /**
     * Proposers d'une famille actifs pendant une période donnée.
     * Filtre par dates d'entrée/sortie de la famille et de l'intervenant.
     */
    public function findByFamillePourPeriode(string $numeroFamille, \DateTimeInterface $firstDay, \DateTimeInterface $lastDay): array
    {
        $rows = $this->conn->fetchAllAssociative(
            "SELECT p.* FROM proposer p
             INNER JOIN intervenants i ON i.numSalarie_Intervenants = p.numSalarie_Intervenants
             INNER JOIN famille f ON f.numero_Famille = p.numero_Famille
             WHERE p.numero_Famille = ?
               AND p.idADH_TypeADH = 'PREST'
               AND (p.idPresta_Prestations = 'MENA' OR p.idPresta_Prestations = 'ENFA')
               AND p.dateDeb_Proposer <= ?
               AND (p.dateFin_Proposer IS NULL
                    OR p.dateFin_Proposer = '0000-00-00'
                    OR p.dateFin_Proposer >= ?)
               AND (i.archive_Intervenants = 0 OR i.archive_Intervenants IS NULL)
               AND (i.dateEntree_Intervenants IS NULL OR i.dateEntree_Intervenants <= ?)
               AND (i.dateSortie_Intervenants IS NULL OR i.dateSortie_Intervenants = '0000-00-00' OR i.dateSortie_Intervenants >= ?)
               AND (f.archive_Famille = 0 OR f.archive_Famille IS NULL)
               AND (f.dateEntree_Famille IS NULL OR f.dateEntree_Famille <= ?)
               AND (f.dateSortie_Famille IS NULL OR f.dateSortie_Famille = '0000-00-00' OR f.dateSortie_Famille >= ?)
             ORDER BY p.jour_Proposer, p.hDeb_Proposer",
            [
                $numeroFamille,
                $lastDay->format('Y-m-d'),   // p.dateDeb_Proposer <= lastDay
                $firstDay->format('Y-m-d'),  // p.dateFin_Proposer >= firstDay
                $lastDay->format('Y-m-d'),   // i.dateEntree <= lastDay
                $firstDay->format('Y-m-d'),  // i.dateSortie >= firstDay
                $lastDay->format('Y-m-d'),   // f.dateEntree <= lastDay
                $firstDay->format('Y-m-d'),  // f.dateSortie >= firstDay
            ]
        );

        return $this->hydrateRows($rows);
    }

    // ── Requêtes scalaires (IDs) ──────────────────────────────────────────────

    /**
     * Numéros de famille distincts liés à un intervenant (actifs).
     *
     * @return string[]
     */
    public function findNumerosFamilleByIntervenant(int $numSalarie): array
    {
        return $this->conn->fetchFirstColumn(
            'SELECT DISTINCT p.numero_Famille FROM proposer p
             WHERE p.numSalarie_Intervenants = ?

               AND ' . self::ACTIF_SQL,
            [$numSalarie]
        );
    }

    /**
     * numSalarie distincts liés à une famille (actifs).
     *
     * @return int[]
     */
    public function findNumsSalarieByFamille(string $numeroFamille): array
    {
        $rows = $this->conn->fetchFirstColumn(
            'SELECT DISTINCT p.numSalarie_Intervenants FROM proposer p
             WHERE p.numero_Famille = ?
             
               AND ' . self::ACTIF_SQL,
            [$numeroFamille]
        );

        return array_map('intval', $rows);
    }

    /**
     * numSalarie distincts assurant un type de prestation (MENA/ENFA) sur un
     * planning ACTIF. Sert de vivier de remplaçants.
     *
     * @return int[]
     */
    public function findNumsSalarieActifsParType(string $type): array
    {
        return array_map('intval', $this->conn->fetchFirstColumn(
            "SELECT DISTINCT p.numSalarie_Intervenants FROM proposer p
             WHERE p.idADH_TypeADH = 'PREST'
               AND p.idPresta_Prestations = ?
               AND (p.dateFin_Proposer IS NULL OR p.dateFin_Proposer = '0000-00-00' OR p.dateFin_Proposer >= CURDATE())",
            [strtoupper($type)]
        ));
    }

    // ── Lookup par clé composite ──────────────────────────────────────────────

    /**
     * Retrouve un proposer par sa PK composite.
     * $hDebHHii : heureDebut au format "HHii" (ex. "0800").
     */
    public function findOneByKey(int $numSalarie, string $numFam, string $type, string $typeAdh, string $jour, string $hDebHHii): ?Proposer
    {
        $hDeb = \DateTime::createFromFormat('Hi', $hDebHHii);
        if (!$hDeb) {
            return null;
        }

        $row = $this->conn->fetchAssociative(
            'SELECT * FROM proposer p
             WHERE p.numSalarie_Intervenants = ?
               AND p.numero_Famille         = ?
               AND p.idPresta_Prestations   = ?
               AND p.idADH_TypeADH          = ?
               AND p.jour_Proposer          = ?
               AND p.hDeb_Proposer          = ?
             LIMIT 1',
            [$numSalarie, $numFam, $type, $typeAdh, $jour, $hDeb->format('H:i:s')]
        );

        if (!$row) {
            return null;
        }

        return $this->hydrateRow($row);
    }

    // ── Vérification ─────────────────────────────────────────────────────────

    /**
     * Vérifie qu'un intervenant est actif chez une famille.
     */
    public function isIntervenantAssignedToFamille(int $numSalarie, string $numeroFamille): bool
    {
        // Cohérent avec findTypesParFamilleForIntervenant (familles prestataires de
        // l'intervenant). On NE requiert PAS un planning encore actif (dateFin) : un
        // intervenant peut pointer/déclarer pour une famille dont le planning a expiré.
        // Le filtre prestataire (mand=0, PGE/PM, non archivée) reste appliqué.
        $count = $this->conn->fetchOne(
            "SELECT COUNT(*) FROM proposer p
             INNER JOIN famille f ON f.numero_Famille = p.numero_Famille
             WHERE p.numSalarie_Intervenants = ?
               AND p.numero_Famille = ?
               AND p.idADH_TypeADH = 'PREST'
               AND (p.idPresta_Prestations = 'MENA' OR p.idPresta_Prestations = 'ENFA')
               AND (f.archive_Famille = 0 OR f.archive_Famille IS NULL)
               AND (f.mand_Famille = 0 OR f.mand_Famille IS NULL)
               AND ((f.PGE_Famille IS NOT NULL AND f.PGE_Famille != '')
                    OR (f.PM_Famille IS NOT NULL AND f.PM_Famille != ''))",
            [$numSalarie, $numeroFamille]
        );

        return (int)$count > 0;
    }

    // ── Prochain créneau ──────────────────────────────────────────────────────

    /**
     * Prochain créneau de l'intervenant dans les 7 prochains jours.
     * Cherche d'abord aujourd'hui (heure >= maintenant) puis les jours suivants.
     */
    public function findNextSlot(int $numSalarie): ?Proposer
    {
        $now   = new \DateTime();
        $today = $this->toFrenchDay($now->format('l'));
        $jours = $this->getJoursDepuisAujourdhui($today);

        // Créneau encore à venir aujourd'hui
        $rows = $this->conn->fetchAllAssociative(
            'SELECT * FROM proposer p
             WHERE p.numSalarie_Intervenants = ?
               AND p.jour_Proposer = ?
               AND p.hDeb_Proposer >= ?
               AND ' . self::ACTIF_SQL . '
             ORDER BY p.hDeb_Proposer
             LIMIT 1',
            [$numSalarie, $today, $now->format('H:i:s')]
        );

        if (!empty($rows)) {
            return $this->hydrateRow($rows[0]);
        }

        // Premier créneau dans les 6 jours suivants
        foreach (array_slice($jours, 1) as $jour) {
            $rows = $this->conn->fetchAllAssociative(
                'SELECT * FROM proposer p
                 WHERE p.numSalarie_Intervenants = ?
                   AND p.jour_Proposer = ?
                   AND ' . self::ACTIF_SQL . '
                 ORDER BY p.hDeb_Proposer
                 LIMIT 1',
                [$numSalarie, $jour]
            );

            if (!empty($rows)) {
                return $this->hydrateRow($rows[0]);
            }
        }

        return null;
    }

    // ── Hydratation manuelle (évite les problèmes de date 0000-00-00) ─────────

    /** @return Proposer[] */
    private function hydrateRows(array $rows): array
    {
        return array_values(array_filter(array_map(
            fn(array $r) => $this->hydrateRow($r),
            $rows
        )));
    }

    private function hydrateRow(array $row): ?Proposer
    {
        try {
            $p = new Proposer();
            $p->setTypePrestation($row['idPresta_Prestations']);
            $p->setNumSalarie((int)$row['numSalarie_Intervenants']);
            $p->setNumeroFamille($row['numero_Famille']);
            $p->setTypeAdh($row['idADH_TypeADH']);
            $p->setJour($row['jour_Proposer']);
            $p->setHeureDebut($this->parseTime($row['hDeb_Proposer']));
            $p->setHeureFin(isset($row['hFin_Proposer']) ? $this->parseTime($row['hFin_Proposer']) : null);
            // 0000-00-00 → null → sentinel 1900-01-01 pour différencier "pas de date" de toute date réelle
            $p->setDateDeb($this->parseDate($row['DateDeb_Proposer']) ?? new \DateTime('1900-01-01'));
            $p->setDateFin($this->parseDate($row['dateFin_Proposer'] ?? null));
            $p->setStatut($row['Statut_Proposer'] ?? 'En attente');
            $p->setModalites($row['modalites_Proposer'] ?? null);
            $p->setValidIntervenant(isset($row['validInterv_Proposer']) ? (bool)$row['validInterv_Proposer'] : null);
            $p->setValidFamille(isset($row['validFamille_Proposer']) ? (bool)$row['validFamille_Proposer'] : null);
            $p->setOptions($row['options_Proposer'] ?? null);
            $p->setDateModif($row['dateModif_Proposer'] ?? null);
            $p->setFrequence(isset($row['frequence_Proposer']) ? (int)$row['frequence_Proposer'] : null);
            return $p;
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDate(?string $d): ?\DateTime
    {
        if (!$d || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') {
            return null;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $d);
        return $dt ?: null;
    }

    private function parseTime(string $t): \DateTime
    {
        $dt = \DateTime::createFromFormat('H:i:s', $t);
        return $dt ?: new \DateTime('00:00:00');
    }

    // ── Utilitaires jour ──────────────────────────────────────────────────────

    /** @return string[] */
    private function getJoursDepuisAujourdhui(string $today): array
    {
        $ordre = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $idx   = array_search($today, $ordre, true);
        if ($idx === false) {
            return $ordre;
        }
        return array_merge(array_slice($ordre, $idx), array_slice($ordre, 0, $idx));
    }

    /**
     * Retourne toutes les occurrences planifiées pour une famille sur un mois donné.
     * Chaque élément : ['date' => \DateTime, 'proposer' => Proposer]
     *
     * @return array<int, array{date: \DateTime, proposer: Proposer}>
     */
    public function expandForMonth(string $numFam, int $year, int $month): array
    {
        $firstDay   = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $lastDay    = (clone $firstDay)->modify('last day of this month');
        // Charger uniquement les proposers actifs PENDANT ce mois (pas CURDATE)
        $entries    = $this->findByFamillePourPeriode($numFam, $firstDay, $lastDay);
        $occurrences = [];

        $dayMap = [
            'lundi'     => 1, 'mardi'  => 2, 'mercredi' => 3,
            'jeudi'     => 4, 'vendredi' => 5, 'samedi' => 6, 'dimanche' => 7,
        ];

        foreach ($entries as $proposer) {
            $this->expandProposerIntoMonth($proposer, $firstDay, $lastDay, $dayMap, $occurrences);
        }

        usort($occurrences, fn($a, $b) => $a['date'] <=> $b['date']);
        return $occurrences;
    }

    /**
     * Proposers d'un intervenant actifs pendant une période donnée.
     * Filtre par dates d'entrée/sortie de l'intervenant et de la famille.
     */
    public function findByIntervenantPourPeriode(int $numSalarie, \DateTimeInterface $firstDay, \DateTimeInterface $lastDay): array
    {
        $rows = $this->conn->fetchAllAssociative(
            "SELECT p.* FROM proposer p
             INNER JOIN intervenants i ON i.numSalarie_Intervenants = p.numSalarie_Intervenants
             INNER JOIN famille f ON f.numero_Famille = p.numero_Famille
             WHERE p.numSalarie_Intervenants = ?
               AND p.idADH_TypeADH = 'PREST'
               AND (p.idPresta_Prestations = 'MENA' OR p.idPresta_Prestations = 'ENFA')
               AND p.dateDeb_Proposer <= ?
               AND (p.dateFin_Proposer IS NULL
                    OR p.dateFin_Proposer = '0000-00-00'
                    OR p.dateFin_Proposer >= ?)
               AND (i.archive_Intervenants = 0 OR i.archive_Intervenants IS NULL)
               AND (i.dateEntree_Intervenants IS NULL OR i.dateEntree_Intervenants <= ?)
               AND (i.dateSortie_Intervenants IS NULL OR i.dateSortie_Intervenants = '0000-00-00' OR i.dateSortie_Intervenants >= ?)
               AND (f.archive_Famille = 0 OR f.archive_Famille IS NULL)
               AND (f.dateEntree_Famille IS NULL OR f.dateEntree_Famille <= ?)
               AND (f.dateSortie_Famille IS NULL OR f.dateSortie_Famille = '0000-00-00' OR f.dateSortie_Famille >= ?)
               AND f.numero_Famille != '9999'
             ORDER BY p.jour_Proposer, p.hDeb_Proposer",
            [
                $numSalarie,
                $lastDay->format('Y-m-d'),
                $firstDay->format('Y-m-d'),
                $lastDay->format('Y-m-d'),
                $firstDay->format('Y-m-d'),
                $lastDay->format('Y-m-d'),
                $firstDay->format('Y-m-d'),
            ]
        );

        return $this->hydrateRows($rows);
    }

    /**
     * Occurrences planifiées d'un intervenant sur un mois donné.
     * Chaque élément : ['date' => \DateTime, 'proposer' => Proposer]
     *
     * @return array<int, array{date: \DateTime, proposer: Proposer}>
     */
    public function expandForMonthIntervenant(int $numSalarie, int $year, int $month): array
    {
        $firstDay = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $lastDay  = (clone $firstDay)->modify('last day of this month');

        return $this->expandForPeriodIntervenant($numSalarie, $firstDay, $lastDay);
    }

    /**
     * Occurrences planifiées d'un intervenant sur une plage arbitraire (ex. 25 M-1 → 24 M).
     * Chaque élément : ['date' => \DateTime, 'proposer' => Proposer]
     *
     * @return array<int, array{date: \DateTime, proposer: Proposer}>
     */
    public function expandForPeriodIntervenant(int $numSalarie, \DateTime $firstDay, \DateTime $lastDay): array
    {
        $entries = $this->findByIntervenantPourPeriode($numSalarie, $firstDay, $lastDay);

        $dayMap = [
            'lundi'     => 1, 'mardi'  => 2, 'mercredi' => 3,
            'jeudi'     => 4, 'vendredi' => 5, 'samedi' => 6, 'dimanche' => 7,
        ];

        $occurrences = [];
        foreach ($entries as $proposer) {
            $this->expandProposerIntoMonth($proposer, $firstDay, $lastDay, $dayMap, $occurrences);
        }

        usort($occurrences, fn($a, $b) => $a['date'] <=> $b['date']);
        return $occurrences;
    }

    /**
     * Injecte dans $out les occurrences d'un proposer sur la plage [firstDay, lastDay].
     *
     * Règle date de début :
     *  - Si DateDeb_Proposer était 0000-00-00 (stocké comme 1900-01-01), le proposer
     *    est réputé actif depuis toujours. On utilise firstDay comme référence de
     *    calcul de fréquence (semaine 0 = première semaine du mois visé), ce qui
     *    évite d'afficher "Depuis le 01/01/1900" et donne une parité cohérente.
     *  - Sinon, le proposer n'est actif qu'à partir de sa vraie dateDeb.
     *
     * @param array<int, array{date:\DateTime, proposer:Proposer}> $out
     */
    private function expandProposerIntoMonth(
        Proposer   $proposer,
        \DateTime  $firstDay,
        \DateTime  $lastDay,
        array      $dayMap,
        array     &$out
    ): void {
        $targetDow = $dayMap[mb_strtolower(trim($proposer->getJour()))] ?? null;
        if ($targetDow === null) {
            return;
        }

        $freq    = max(1, $proposer->getFrequence() ?? 1);
        $dateDeb = $proposer->getDateDeb();
        $dateFin = $proposer->getDateFin();

        // 1900-01-01 = sentinel "aucune date de début définie" (0000-00-00 en DB)
        $noStartDate = ((int)$dateDeb->format('Y') < 1950);

        // Référence pour le calcul de parité de fréquence bi-hebdomadaire
        $refDate = $noStartDate ? $firstDay : $dateDeb;

        $cur = clone $firstDay;
        while ($cur <= $lastDay) {
            if ((int)$cur->format('N') === $targetDow) {
                $afterStart  = $noStartDate || $cur >= $dateDeb;
                $beforeEnd   = $dateFin === null || $cur <= $dateFin;
                if ($afterStart && $beforeEnd) {
                    $diff    = (int)$refDate->diff($cur)->days;
                    $weekIdx = (int)floor($diff / 7);
                    if ($weekIdx % $freq === 0) {
                        $out[] = ['date' => clone $cur, 'proposer' => $proposer];
                    }
                }
            }
            $cur->modify('+1 day');
        }
    }

    private function toFrenchDay(string $englishDay): string
    {
        return match ($englishDay) {
            'Monday'    => 'lundi',
            'Tuesday'   => 'mardi',
            'Wednesday' => 'mercredi',
            'Thursday'  => 'jeudi',
            'Friday'    => 'vendredi',
            'Saturday'  => 'samedi',
            'Sunday'    => 'dimanche',
            default     => mb_strtolower($englishDay),
        };
    }

    public function findAllTypeAdh(): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT idADH_TypeADH, intitule_TypeADH FROM typeadh ORDER BY intitule_TypeADH ASC'
        );
    }

    public function save(Proposer $proposer): void
    {
        $this->conn->insert('proposer', [
            'idPresta_Prestations'    => $proposer->getTypePrestation(),
            'numSalarie_Intervenants' => $proposer->getNumSalarie(),
            'numero_Famille'          => $proposer->getNumeroFamille(),
            'idADH_TypeADH'           => $proposer->getTypeAdh(),
            'jour_Proposer'           => $proposer->getJour(),
            'hDeb_Proposer'           => $proposer->getHeureDebut()->format('H:i:s'),
            'hFin_Proposer'           => $proposer->getHeureFin()?->format('H:i:s'),
            'DateDeb_Proposer'        => $proposer->getDateDeb()->format('Y-m-d'),
            'dateFin_Proposer'        => $proposer->getDateFin()?->format('Y-m-d'),
            'Statut_Proposer'         => $proposer->getStatut() ?? 'En attente',
            'frequence_Proposer'      => $proposer->getFrequence(),
            'dateModif_Proposer'      => $proposer->getDateModif(),
        ]);
    }
}
