<?php

namespace App\Repository;

use App\Entity\Principal\Candidat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Candidat>
 */
class CandidatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidat::class);
    }

    /**
     * Cherche un candidat par numSS en essayant plusieurs formats.
     *
     * Les SS sont stockés dans la DB avec des séparateurs variables
     * (points, espaces, ou aucun).  On teste toutes les variantes.
     */
    public function findByNumSs(string $numSs): ?Candidat
    {
        $candidates = array_unique(array_filter([
            $numSs,
            str_replace('.', ' ', $numSs),
            str_replace(' ', '.', $numSs),
        ]));

        // Version chiffres seuls (seulement si ça ressemble à un numSS : 15 chiffres)
        $digitsOnly = preg_replace('/\D/', '', $numSs);
        if (strlen($digitsOnly) === 15) {
            $candidates[] = $digitsOnly;
        }

        foreach ($candidates as $candidate) {
            $result = $this->createQueryBuilder('c')
                ->where('c.numSs = :numSs')
                ->setParameter('numSs', $candidate)
                ->getQuery()
                ->getOneOrNullResult();

            if ($result) return $result;
        }

        return null;
    }
}
