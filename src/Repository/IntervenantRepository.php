<?php

namespace App\Repository;

use App\Entity\Intervenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Intervenant>
 *
 * @method Intervenant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Intervenant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Intervenant[]    findAll()
 * @method Intervenant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IntervenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Intervenant::class);
    }

    /**
     * Retourne tous les intervenants non archivés
     */
    public function findAllNonArchived(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.archive = :archive')
            ->setParameter('archive', 0)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les informations d'un intervenant
     */
    public function findInfosIntervenant(int $id): ?Intervenant
    {
        return $this->createQueryBuilder('i')
            ->where('i.id = :id')
            ->andWhere('i.archive = :archive')
            ->setParameter('id', $id)
            ->setParameter('archive', 0)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche un intervenant par son numéro de salarié
     */
    public function findByNumSalarie(string $numSalarie): ?Intervenant
    {
        return $this->createQueryBuilder('i')
            ->where('i.numSalarie = :numSalarie')
            ->andWhere('i.archive = :archive')
            ->setParameter('numSalarie', $numSalarie)
            ->setParameter('archive', 0)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche des intervenants par nom ou prénom
     */
    public function findByNomOrPrenom(string $search): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.nom LIKE :search')
            ->orWhere('i.prenom LIKE :search')
            ->andWhere('i.archive = :archive')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('archive', 0)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'intervenants actifs
     */
    public function countActifs(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.archive = :archive')
            ->setParameter('archive', 0)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retourne les intervenants disponibles pour une date donnée
     */
    public function findDisponiblesPourDate(\DateTimeInterface $date): array
    {
        // Cette méthode pourrait être améliorée avec la table de disponibilités
        return $this->createQueryBuilder('i')
            ->where('i.archive = :archive')
            ->andWhere('i.dateEntree <= :date OR i.dateEntree IS NULL')
            ->andWhere('(i.dateSortie >= :date OR i.dateSortie IS NULL)')
            ->setParameter('archive', 0)
            ->setParameter('date', $date)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
