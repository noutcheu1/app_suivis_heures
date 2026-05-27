<?php

namespace App\Repository;

use App\Entity\Principal\Enfant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enfant>
 */
class EnfantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enfant::class);
    }

    /**
     * Enfants d'une famille concernés par la garde, du plus jeune au plus âgé.
     *
     * @return Enfant[]
     */
    public function findByFamille(string $numFam, bool $gardeUniquement = false): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.numeroFamille = :num')
            ->setParameter('num', $numFam)
            ->orderBy('e.dateNaiss', 'DESC');

        if ($gardeUniquement) {
            $qb->andWhere('e.concernGarde = :garde')
               ->setParameter('garde', true);
        }

        return $qb->getQuery()->getResult();
    }
}
