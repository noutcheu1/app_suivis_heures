<?php

namespace App\Repository;

use App\Entity\Horaireinter;
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
        
        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');
        
        $sql = "
            SELECT SUM(TIMESTAMPDIFF(SECOND, heureDebutPresta, heureFinPresta) / 3600) as total
            FROM horaireinter h
            WHERE h.datePresta BETWEEN :startDate AND :endDate
            AND h.desactiver = 0
        ";
        
        $stmt = $this->getEntityManager()->createNativeQuery($sql, new \Doctrine\ORM\Query\ResultSetMapping());
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
        $endDate = null;
        
        if ($mois) {
            [$month, $year] = explode('/', $mois);
            $startDate = new \DateTime("$year-$month-01");
            $endDate = (clone $startDate)->modify('last day of this month');
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
        
        $stmt = $this->getEntityManager()->createNativeQuery($sql, new \Doctrine\ORM\Query\ResultSetMapping());
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
     * @param int $familleId Numéro de la famille
     * @param string $mois Format: 'mm/YYYY'
     */
    public function countByFamille(int $familleId, string $mois): float
    {
        [$month, $year] = explode('/', $mois);
        
        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');
        
        $sql = "
            SELECT SUM(TIMESTAMPDIFF(SECOND, heureDebutPresta, heureFinPresta) / 3600) as total
            FROM horaireinter h
            WHERE h.numFam = :numFam
            AND h.datePresta BETWEEN :startDate AND :endDate
            AND h.desactiver = 0
        ";
        
        $stmt = $this->getEntityManager()->createNativeQuery($sql, new \Doctrine\ORM\Query\ResultSetMapping());
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
     * Récupérer toutes les prestations non validées d'un mois
     */
    public function findNonValidatedByMonth(string $mois): array
    {
        [$month, $year] = explode('/', $mois);
        
        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');
        
        return $this->createQueryBuilder('h')
            ->where('h.datePresta BETWEEN :startDate AND :endDate')
            ->andWhere('h.validerFam = :validerFam')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('validerFam', false)
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
     * Récupérer les prestations non validées d'une famille
     */
    public function findNonValidatedByFamille(string $numFam): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.numFam = :numFam')
            ->andWhere('h.validerFam = :validerFam')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numFam', $numFam)
            ->setParameter('validerFam', false)
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

    public function findByIntervenantPeriodType(int $numInter, \DateTimeInterface $startDate, \DateTimeInterface $endDate, string $type): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.numInter = :numInter')
            ->andWhere('h.datePresta BETWEEN :startDate AND :endDate')
            ->andWhere('h.typePresta = :type')
            ->andWhere('h.desactiver = :desactiver')
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
     * Compter les heures validées d'un mois par famille
     */
    public function countValidatedByFamilleAndMonth(int $familleId, string $mois): float
    {
        [$month, $year] = explode('/', $mois);
        
        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');
        
        $sql = "
            SELECT SUM(TIMESTAMPDIFF(SECOND, heureDebutPresta, heureFinPresta) / 3600) as total
            FROM horaireinter h
            WHERE h.numFam = :numFam
            AND h.datePresta BETWEEN :startDate AND :endDate
            AND h.validerFam = 1
            AND h.desactiver = 0
        ";
        
        $stmt = $this->getEntityManager()->createNativeQuery($sql, new \Doctrine\ORM\Query\ResultSetMapping());
        $stmt->setParameter('numFam', $familleId);
        $stmt->setParameter('startDate', $startDate->format('Y-m-d'));
        $stmt->setParameter('endDate', $endDate->format('Y-m-d'));
        
        $result = $stmt->getOneOrNullResult();
        
        return (float)($result['total'] ?? 0);
    }
}