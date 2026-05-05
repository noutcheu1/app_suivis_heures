<?php

namespace App\Repository;

use App\Entity\Famille;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Famille>
 *
 * @method Famille|null find($id, $lockMode = null, $lockVersion = null)
 * @method Famille|null findOneBy(array $criteria, array $orderBy = null)
 * @method Famille[]    findAll()
 * @method Famille[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FamilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Famille::class);
    }

    /**
     * Retourne toutes les familles non archivées
     */
    public function findAllNonArchived(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.archive = :archive OR f.archive IS NULL')
            ->setParameter('archive', 0)
            ->orderBy('f.nomFamille', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne une famille par son numéro
     */
    public function findByNumero(string $numero): ?Famille
    {
        return $this->createQueryBuilder('f')
            ->where('f.numeroFamille = :numero')
            ->andWhere('f.archive = :archive OR f.archive IS NULL')
            ->setParameter('numero', $numero)
            ->setParameter('archive', 0)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Recherche des familles par nom ou ville
     */
    public function findByNomOrVille(string $search): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.nomFamille LIKE :search')
            ->orWhere('f.ville LIKE :search')
            ->andWhere('f.archive = :archive OR f.archive IS NULL')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('archive', 0)
            ->orderBy('f.nomFamille', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les familles par ville
     */
    public function findByVille(string $ville): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.ville = :ville')
            ->andWhere('f.archive = :archive OR f.archive IS NULL')
            ->setParameter('ville', $ville)
            ->setParameter('archive', 0)
            ->orderBy('f.nomFamille', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de familles actives
     */
    public function countActives(): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.archive = :archive OR f.archive IS NULL')
            ->setParameter('archive', 0)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
