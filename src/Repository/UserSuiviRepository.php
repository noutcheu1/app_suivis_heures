<?php

namespace App\Repository;

use App\Entity\Horaire\UserSuivi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSuivi>
 *
 * @method UserSuivi|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserSuivi|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserSuivi[]    findAll()
 * @method UserSuivi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSuiviRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSuivi::class);
    }

    /**
     * Trouve un utilisateur par son identifiant (numéro de sécurité sociale)
     */
    public function findByIdentifiant(string $identifiant): ?UserSuivi
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $identifiant)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function identifiantExists(string $identifiant): bool
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.username = :username')
            ->setParameter('username', $identifiant)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function countUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
