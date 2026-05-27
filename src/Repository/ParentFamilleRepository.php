<?php

namespace App\Repository;

use App\Entity\Principal\ParentFamille;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParentFamilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParentFamille::class);
    }

    /** @return ParentFamille[] */
    public function findByFamille(string $numeroFamille): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.numeroFamille = :num')
            ->setParameter('num', $numeroFamille)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
