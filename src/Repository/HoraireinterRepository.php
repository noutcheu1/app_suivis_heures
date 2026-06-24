<?php

namespace App\Repository;

use App\Entity\Horaire\Horaireinter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Horaireinter>
 */
class HoraireinterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Horaireinter::class);
    }

    /**
     * Compter les heures d'un mois spécifique
     * @param string $mois Format: 'mm/YYYY' (ex: '05/2026')
     */
    public function countByMonth(string $mois): float
    {
        [$month, $year] = explode('/', $mois);

        $startDate = new \DateTime(sprintf('%04d-%02d-25', $year, $month));
        $startDate->modify('-1 month');
        $endDate   = new \DateTime(sprintf('%04d-%02d-24', $year, $month));
        
        $sql = "
            SELECT SUM(TIMESTAMPDIFF(SECOND, heureDebutPresta, heureFinPresta) / 3600) as total
            FROM horaireinter h
            WHERE h.datePresta BETWEEN :startDate AND :endDate
            AND h.desactiver = 0
        ";
        
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('total', 'total');
        $stmt = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $stmt->setParameter('startDate', $startDate->format('Y-m-d'));
        $stmt->setParameter('endDate', $endDate->format('Y-m-d'));

        $result = $stmt->getOneOrNullResult();

        return (float)($result['total'] ?? 0);
    }

    /**
     * Compter les heures par intervenant
     * @param int $intervenantId Numéro de l'intervenant
     * @param string|null $mois Format: 'mm/YYYY' (optionnel)
     */
    public function countByIntervenant(int $intervenantId, ?string $mois = null): float
    {
        $startDate = null;
        $endDate   = null;

        if ($mois) {
            [$month, $year] = explode('/', $mois);
            $startDate = new \DateTime(sprintf('%04d-%02d-25', $year, $month));
            $startDate->modify('-1 month');
            $endDate   = new \DateTime(sprintf('%04d-%02d-24', $year, $month));
        }

        $sql = "
            SELECT SUM(TIMESTAMPDIFF(SECOND, heureDebutPresta, heureFinPresta) / 3600) as total
            FROM horaireinter h
            WHERE h.numInter = :numInter
            AND h.desactiver = 0
        ";

        if ($startDate && $endDate) {
            $sql .= " AND h.datePresta BETWEEN :startDate AND :endDate";
        }

        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('total', 'total');
        $stmt = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $stmt->setParameter('numInter', $intervenantId);

        if ($startDate && $endDate) {
            $stmt->setParameter('startDate', $startDate->format('Y-m-d'));
            $stmt->setParameter('endDate', $endDate->format('Y-m-d'));
        }

        $result = $stmt->getOneOrNullResult();

        return (float)($result['total'] ?? 0);
    }

    /**
     * Compter les heures par famille et mois
     * @param string $familleId Numéro de la famille
     * @param string $mois Format: 'mm/YYYY'
     */
    public function countByFamille(string $familleId, string $mois): float
    {
        [$month, $year] = explode('/', $mois);

        $startDate = new \DateTime(sprintf('%04d-%02d-25', $year, $month));
        $startDate->modify('-1 month');
        $endDate   = new \DateTime(sprintf('%04d-%02d-24', $year, $month));

        $sql = "
            SELECT SUM(TIMESTAMPDIFF(SECOND, heureDebutPresta, heureFinPresta) / 3600) as total
            FROM horaireinter h
            WHERE h.numFam = :numFam
            AND h.datePresta BETWEEN :startDate AND :endDate
            AND h.desactiver = 0
        ";

        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('total', 'total');
        $stmt = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $stmt->setParameter('numFam', $familleId);
        $stmt->setParameter('startDate', $startDate->format('Y-m-d'));
        $stmt->setParameter('endDate', $endDate->format('Y-m-d'));

        $result = $stmt->getOneOrNullResult();

        return (float)($result['total'] ?? 0);
    }

    /**
     * Récupérer toutes les prestations d'un mois
     */
    public function findByMonth(string $mois): array
    {
        [$month, $year] = explode('/', $mois);
        
        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');
        
        return $this->createQueryBuilder('h')
            ->where('h.datePresta BETWEEN :startDate AND :endDate')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer toutes les prestations non déclarées par la famille pour un mois
     */
    public function findNonDeclaredByMonth(string $mois): array
    {
        [$month, $year] = explode('/', $mois);

        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');

        return $this->createQueryBuilder('h')
            ->where('h.datePresta BETWEEN :startDate AND :endDate')
            ->andWhere('h.declarerLeFam IS NULL')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer toutes les prestations d'un intervenant
     */
    public function findByIntervenant(int $numInter): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.numInter = :numInter')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numInter', $numInter)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer toutes les prestations d'une famille
     */
    public function findByFamille(string $numFam): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.numFam = :numFam')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numFam', $numFam)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Prestations d'une famille pour un mois donné (YYYY-MM).
     * Triées par date puis heure de début.
     *
     * @return Horaireinter[]
     */
    /**
     * Toutes les prestations d'un mois toutes familles confondues,
     * triées par famille puis date/heure.
     * @return Horaireinter[]
     */
    public function findAllForMonth(int $year, int $month): array
    {
        $start = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $end   = (clone $start)->modify('last day of this month');

        return $this->createQueryBuilder('h')
            ->where('h.datePresta BETWEEN :start AND :end')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('desactiver', false)
            ->orderBy('h.numFam', 'ASC')
            ->addOrderBy('h.datePresta', 'ASC')
            ->addOrderBy('h.heureDebutPresta', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByFamilleForMonth(string $numFam, int $year, int $month): array
    {
        $start = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $end   = (clone $start)->modify('last day of this month');

        return $this->createQueryBuilder('h')
            ->where('h.numFam = :numFam')
            ->andWhere('h.datePresta BETWEEN :start AND :end')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numFam', $numFam)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'ASC')
            ->addOrderBy('h.heureDebutPresta', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByFamilleEtMois(string $numFam, string $moisAnnee): array
    {
        // Accepte mm/YYYY (format français) ou YYYY-MM (ISO, vient de <input type="month">)
        if (str_contains($moisAnnee, '/')) {
            [$month, $year] = explode('/', $moisAnnee);
        } else {
            [$year, $month] = explode('-', $moisAnnee);
        }
        // Période : 25 du mois précédent au 24 du mois courant
        $start = new \DateTime(sprintf('%04d-%02d-25', $year, $month));
        $start->modify('-1 month');
        $end   = new \DateTime(sprintf('%04d-%02d-24', $year, $month));

        return $this->createQueryBuilder('h')
            ->where('h.numFam = :numFam')
            ->andWhere('h.datePresta BETWEEN :start AND :end')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numFam', $numFam)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'ASC')
            ->addOrderBy('h.heureDebutPresta', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les prestations non déclarées par une famille
     */
    public function findNonDeclaredByFamille(string $numFam): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.numFam = :numFam')
            ->andWhere('h.declarerLeFam IS NULL')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numFam', $numFam)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les heures entre deux dates
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.datePresta BETWEEN :startDate AND :endDate')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByIntervenantForMonth(int $numInter, int $year, int $month): array
    {
        $start = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $end   = (clone $start)->modify('last day of this month');

        return $this->createQueryBuilder('h')
            ->where('h.numInter = :numInter')
            ->andWhere('h.datePresta BETWEEN :start AND :end')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numInter', $numInter)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'ASC')
            ->addOrderBy('h.heureDebutPresta', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByIntervenantPeriodType(int $numInter, \DateTimeInterface $startDate, \DateTimeInterface $endDate, string $type): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.numInter = :numInter')
            ->andWhere('h.datePresta BETWEEN :startDate AND :endDate')
            ->andWhere('h.typePresta = :type')
            ->andWhere('h.desactiver = :desactiver')
            // Exclut les pointages encore en cours (début sans fin) : pas une heure
            // déclarée tant qu'il n'y a pas d'heure de fin.
            ->andWhere('h.heureFinPresta IS NOT NULL')
            ->setParameter('numInter', $numInter)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('type', $type)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'ASC')
            ->addOrderBy('h.heureDebutPresta', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le pointage « en cours » d'un intervenant pour une famille aujourd'hui :
     * une ligne démarrée (heureDebutPresta rempli) mais pas encore terminée
     * (heureFinPresta NULL). Sert au pointage QR sans connexion (Démarrer/Terminer).
     */
    public function findEnCours(int $numInter, ?string $numFam): ?Horaireinter
    {
        $debutJour = new \DateTime('today 00:00:00');
        $finJour   = new \DateTime('today 23:59:59');

        $qb = $this->createQueryBuilder('h')
            ->where('h.numInter = :numInter')
            ->andWhere('h.datePresta BETWEEN :debut AND :fin')
            ->andWhere('h.heureFinPresta IS NULL')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numInter', $numInter)
            ->setParameter('debut', $debutJour)
            ->setParameter('fin', $finJour)
            ->setParameter('desactiver', false)
            ->orderBy('h.heureDebutPresta', 'DESC')
            ->setMaxResults(1);

        // Occasionnel : numFam null/0 → on cherche les pointages sans famille rattachée.
        if ($numFam === null || $numFam === '' || $numFam === '0') {
            $qb->andWhere('h.numFam IS NULL OR h.numFam = :zero')->setParameter('zero', '0');
        } else {
            $qb->andWhere('h.numFam = :numFam')->setParameter('numFam', $numFam);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findDistinctFamilleNumsByIntervenant(int $numInter): array
    {
        $rows = $this->createQueryBuilder('h')
            ->select('h.numFam')
            ->where('h.numInter = :numInter')
            ->andWhere('h.desactiver = :desactiver')
            ->andWhere('h.numFam IS NOT NULL')
            ->setParameter('numInter', $numInter)
            ->setParameter('desactiver', false)
            ->groupBy('h.numFam')
            ->getQuery()
            ->getArrayResult();
        return array_column($rows, 'numFam');
    }

    /**
     * Compter les heures déclarées par la famille pour un mois donné (utilise heureDebutFam/heureFinFam)
     */
    public function countDeclaredByFamilleAndMonth(string $numFam, string $mois): float
    {
        [$month, $year] = explode('/', $mois);

        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');

        $sql = "
            SELECT SUM(TIMESTAMPDIFF(SECOND, heureDebutFam, heureFinFam) / 3600) as total
            FROM horaireinter h
            WHERE h.numFam = :numFam
            AND h.datePresta BETWEEN :startDate AND :endDate
            AND h.declarerLeFam IS NOT NULL
            AND h.desactiver = 0
        ";

        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('total', 'total');
        $stmt = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $stmt->setParameter('numFam', $numFam);
        $stmt->setParameter('startDate', $startDate->format('Y-m-d'));
        $stmt->setParameter('endDate', $endDate->format('Y-m-d'));

        $result = $stmt->getOneOrNullResult();

        return (float)($result['total'] ?? 0);
    }

    public function findByPeriodAndType(string $type, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.typePresta = :type')
            ->andWhere('h.datePresta BETWEEN :start AND :end')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('type', $type)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('desactiver', false)
            ->orderBy('h.numInter', 'ASC')
            ->addOrderBy('h.datePresta', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns distinct (year, month, typePresta) tuples for an intervenant, most recent first.
     */
    /**
     * @param string   $debut       Date début (Y-m-d)
     * @param string   $fin         Date fin (Y-m-d)
     * @param string[] $validFamIds IDs familles prestataires (fournis par FamilleRepository).
     *                              Si vide, aucun filtre famille (toutes les heures comptées).
     */
    public function getStatsPeriodeParIntervenant(string $debut, string $fin, array $validFamIds = [], ?string $type = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $params = ['debut' => $debut, 'fin' => $fin];
        $types  = [];
        $familleWhere = '';
        $typeWhere    = '';

        if (!empty($validFamIds)) {
            $familleWhere = "AND (h.numFam IS NULL OR h.numFam = '0' OR h.numFam IN (:validFamIds))";
            $params['validFamIds'] = $validFamIds;
            $types['validFamIds']  = \Doctrine\DBAL\ArrayParameterType::STRING;
        }

        if ($type !== null) {
            $typeWhere      = 'AND h.typePresta = :type';
            $params['type'] = $type;
        }

        $sql  = "
            SELECT h.numInter, h.typePresta,
                   SUM(TIMESTAMPDIFF(SECOND, h.heureDebutPresta, h.heureFinPresta) / 3600) AS totalHeures,
                   MAX(h.ajouterLe) AS derniereAjout
            FROM horaireinter h
            WHERE h.datePresta BETWEEN :debut AND :fin
              AND h.desactiver = 0
              AND h.numInter > 0
              AND h.heureDebutPresta IS NOT NULL
              AND h.heureFinPresta IS NOT NULL
              $familleWhere
              $typeWhere
            GROUP BY h.numInter, h.typePresta
        ";
        $rows = $conn->executeQuery($sql, $params, $types)->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $id = (int)$row['numInter'];
            $result[$id][$row['typePresta']] = (float)$row['totalHeures'];
            $prev = $result[$id]['derniereAjout'] ?? '0000-00-00 00:00:00';
            $result[$id]['derniereAjout'] = ($row['derniereAjout'] ?? '') > $prev
                ? $row['derniereAjout'] : $prev;
        }
        return $result;
    }

    /**
     * @param int      $numInter
     * @param string[] $validFamIds IDs des familles prestataires valides (fournis par FamilleRepository).
     *                              Si vide, aucun filtre famille n'est appliqué.
     */
    public function findMoisDisponibles(int $numInter, array $validFamIds = []): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $params = ['numInter' => $numInter];
        $types  = [];
        $familleWhere = '';

        if (!empty($validFamIds)) {
            $familleWhere = "AND (h.numFam IS NULL OR h.numFam = '0' OR h.numFam IN (:validFamIds))";
            $params['validFamIds'] = $validFamIds;
            $types['validFamIds']  = \Doctrine\DBAL\ArrayParameterType::STRING;
        }

        $sql = "SELECT DISTINCT YEAR(h.datePresta) AS annee,
                        MONTH(h.datePresta) AS mois,
                        h.typePresta
                 FROM horaireinter h
                 WHERE h.numInter = :numInter
                   AND h.desactiver = 0
                   $familleWhere
                 ORDER BY annee DESC, mois DESC, h.typePresta";

        return $conn->executeQuery($sql, $params, $types)->fetchAllAssociative();
    }

    /**
     * Vérifie si une nouvelle prestation [heureDebut, heureFin] chevauche une prestation
     * existante du même intervenant le même jour (toutes familles confondues).
     *
     * Règle de chevauchement : existDebut < newFin ET existFin > newDebut.
     * (deux créneaux se chevauchent s'ils ne sont pas strictement l'un après l'autre)
     */
    public function existsChevauchement(
        int $numInter,
        \DateTimeInterface $datePresta,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        ?int $excludeId = null
    ): bool {
        $qb = $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->where('h.numInter = :numInter')
            ->andWhere('h.datePresta = :datePresta')
            ->andWhere('h.desactiver = false')
            ->andWhere('h.heureDebutPresta IS NOT NULL')
            ->andWhere('h.heureFinPresta IS NOT NULL')
            ->andWhere('h.heureDebutPresta < :heureFin')
            ->andWhere('h.heureFinPresta > :heureDebut')
            ->setParameter('numInter', $numInter)
            ->setParameter('datePresta', $datePresta)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin);

        if ($excludeId !== null) {
            $qb->andWhere('h.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Vérifie si l'intervenant a déjà une prestation pour la même famille le même jour (même type).
     */
    public function existsDoublonFamilleDate(int $numInter, string $numFam, \DateTimeInterface $datePresta, string $type, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->where('h.numInter = :numInter')
            ->andWhere('h.numFam = :numFam')
            ->andWhere('h.datePresta = :datePresta')
            ->andWhere('h.typePresta = :type')
            ->andWhere('h.desactiver = false')
            ->setParameter('numInter', $numInter)
            ->setParameter('numFam', $numFam)
            ->setParameter('datePresta', $datePresta)
            ->setParameter('type', $type);

        if ($excludeId !== null) {
            $qb->andWhere('h.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Vérifie si une prestation identique existe déjà (doublon).
     * Critères : même intervenant, même date, même heure de début, même type, non désactivée.
     */
    public function existsDoublon(int $numInter, \DateTimeInterface $datePresta, \DateTimeInterface $heureDebut, string $type, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->where('h.numInter = :numInter')
            ->andWhere('h.datePresta = :datePresta')
            ->andWhere('h.heureDebutPresta = :heureDebut')
            ->andWhere('h.typePresta = :type')
            ->andWhere('h.desactiver = false')
            ->setParameter('numInter', $numInter)
            ->setParameter('datePresta', $datePresta)
            ->setParameter('heureDebut', $heureDebut)
            ->setParameter('type', $type);

        if ($excludeId !== null) {
            $qb->andWhere('h.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}