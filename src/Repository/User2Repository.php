<?php

namespace App\Repository;

use App\Entity\User2;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User2>
 *
 * @method User2|null find($id, $lockMode = null, $lockVersion = null)
 * @method User2|null findOneBy(array $criteria, array $orderBy = null)
 * @method User2[]    findAll()
 * @method User2[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class User2Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User2::class);
    }

    /**
     * Trouve un utilisateur par son identifiant (numéro de sécurité sociale)
     */
    public function findByIdentifiant(string $identifiant): ?User2
    {
        return $this->createQueryBuilder('u')
            ->where('u.identifiant = :identifiant')
            ->setParameter('identifiant', $identifiant)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Vérifie si un identifiant existe
     */
    public function identifiantExists(string $identifiant): bool
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.identifiant)')
            ->where('u.identifiant = :identifiant')
            ->setParameter('identifiant', $identifiant)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Compte le nombre total d'utilisateurs
     */
    public function countUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.identifiant)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
