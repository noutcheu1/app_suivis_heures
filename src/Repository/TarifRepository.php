<?php

namespace App\Repository;

use App\Entity\Horaire\Tarif;
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
     * Retourne le tarif actif pour une date donnée (le plus récent dont dateDebut <= date).
     */
    public function findActif(?\DateTimeInterface $date = null): ?Tarif
    {
        $moisAnnee = ($date ?? new \DateTime())->format('Y-m');

        return $this->createQueryBuilder('t')
            ->where('t.dateDebut <= :moisAnnee')
            ->setParameter('moisAnnee', $moisAnnee)
            ->orderBy('t.dateDebut', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne tous les tarifs par ordre chronologique décroissant.
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
