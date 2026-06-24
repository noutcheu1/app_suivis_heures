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

    /**
     * Normalise un numéro de téléphone.
     * Les numéros candidats sont au format "06.12.34.56.78" → on retire les points
     * (et espaces, tirets, parenthèses…), puis on convertit +33 / 0033 → 0.
     */
    public static function normaliserTel(?string $tel): string
    {
        $tel = (string) $tel;
        // Suppression explicite des séparateurs (points, espaces, tirets, slashes…)
        $tel = str_replace(['.', ' ', '-', '/', '(', ')'], '', $tel);
        // On ne garde ensuite que les chiffres (sécurité)
        $d = preg_replace('/\D+/', '', $tel);
        if (str_starts_with($d, '0033')) {
            $d = '0' . substr($d, 4);
        } elseif (str_starts_with($d, '33') && strlen($d) === 11) {
            $d = '0' . substr($d, 2);
        }
        return $d;
    }

    /**
     * Résout un utilisateur par téléphone normalisé.
     * Renvoie null si introuvable OU ambigu (plusieurs comptes avec ce numéro).
     */
    public function findByTelephone(string $tel): ?UserSuivi
    {
        $norm = self::normaliserTel($tel);
        if ($norm === '') {
            return null;
        }
        $res = $this->createQueryBuilder('u')
            ->where('u.telephone = :t')
            ->setParameter('t', $norm)
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        return count($res) === 1 ? $res[0] : null;
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
