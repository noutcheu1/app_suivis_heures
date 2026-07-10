<?php

namespace App\Repository;

use App\Entity\Horaire\IntervenantDispo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IntervenantDispo>
 */
class IntervenantDispoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IntervenantDispo::class);
    }

    /** L'intervenant est-il déclaré disponible pour des remplacements ? */
    public function estDisponible(int $numInter): bool
    {
        return (bool) ($this->find($numInter)?->isDisponible());
    }

    /** Définit la disponibilité (crée la ligne si besoin). */
    public function definir(int $numInter, bool $disponible): void
    {
        $d = $this->find($numInter) ?? new IntervenantDispo($numInter);
        $d->setDisponible($disponible);
        $em = $this->getEntityManager();
        $em->persist($d);
        $em->flush();
    }

    /**
     * numInter des intervenants déclarés disponibles.
     *
     * @return int[]
     */
    public function numsDisponibles(): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('d.numInter')
            ->where('d.disponible = true')
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn ($r) => (int) $r['numInter'], $rows);
    }
}
