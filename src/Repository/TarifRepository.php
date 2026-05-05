<?php

namespace App\Repository;

use App\Entity\Tarif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tarif>
 *
 * @method Tarif|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tarif|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tarif[]    findAll()
 * @method Tarif[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TarifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tarif::class);
    }

    /**
     * Retourne tous les tarifs actifs
     */
    public function findAllActifs(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.dateFinValidite IS NULL OR t.dateFinValidite >= :currentDate')
            ->andWhere('t.dateDebutValidite <= :currentDate')
            ->setParameter('currentDate', date('Y-m'))
            ->orderBy('t.typePrestation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne un tarif par type de prestation
     */
    public function findByTypePrestation(string $typePrestation): ?Tarif
    {
        return $this->createQueryBuilder('t')
            ->where('t.typePrestation = :type')
            ->andWhere('(t.dateFinValidite IS NULL OR t.dateFinValidite >= :currentDate)')
            ->andWhere('t.dateDebutValidite <= :currentDate')
            ->setParameter('type', $typePrestation)
            ->setParameter('currentDate', date('Y-m'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les tarifs valides pour une période donnée
     */
    public function findValidForPeriod(string $periode): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.dateDebutValidite <= :periode')
            ->andWhere('(t.dateFinValidite IS NULL OR t.dateFinValidite >= :periode)')
            ->setParameter('periode', $periode)
            ->orderBy('t.typePrestation', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
