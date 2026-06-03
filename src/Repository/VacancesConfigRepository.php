<?php

namespace App\Repository;

use App\Entity\Horaire\VacancesConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VacancesConfig>
 */
class VacancesConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VacancesConfig::class);
    }

    /** Retourne la config de vacances active dont la fenêtre de dates est valide, ou null. */
    public function findActif(): ?VacancesConfig
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('v')
            ->where('v.actif = :actif')
            ->andWhere('v.dateApparitionDebut IS NULL OR v.dateApparitionDebut <= :today')
            ->andWhere('v.dateApparitionFin IS NULL OR v.dateApparitionFin >= :today')
            ->setParameter('actif', true)
            ->setParameter('today', $today)
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Toutes les configs, les plus récentes en premier. */
    public function findAllOrderedDesc(): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Désactive toutes les configs (avant d'en activer une nouvelle). */
    public function desactiverTout(): void
    {
        $this->createQueryBuilder('v')
            ->update()
            ->set('v.actif', ':false')
            ->setParameter('false', false)
            ->getQuery()
            ->execute();
    }
}
