<?php

namespace App\Repository;

use App\Entity\Horaire\Conge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conge>
 */
class CongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conge::class);
    }

    /**
     * Congés actifs (non annulés) d'une personne, les plus récents d'abord.
     *
     * @return Conge[]
     */
    public function findActifsByPersonne(string $typePersonne, string $personneId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.typePersonne = :t')
            ->andWhere('c.personneId = :p')
            ->andWhere('c.annuleLe IS NULL')
            ->setParameter('t', $typePersonne)
            ->setParameter('p', $personneId)
            ->orderBy('c.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Congés actifs d'une personne qui CHEVAUCHENT une période donnée.
     * Sert au matching remplacement et à l'anti-doublon.
     *
     * @return Conge[]
     */
    /**
     * @param string[]|null $statuts Filtre optionnel sur le statut (VALIDE/EN_ATTENTE/REFUSE).
     */
    public function findChevauchant(string $typePersonne, string $personneId, \DateTimeInterface $debut, \DateTimeInterface $fin, ?int $exclureId = null, ?array $statuts = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.typePersonne = :t')
            ->andWhere('c.personneId = :p')
            ->andWhere('c.annuleLe IS NULL')
            // chevauchement : debut <= fin_periode AND fin >= debut_periode
            ->andWhere('c.dateDebut <= :fin')
            ->andWhere('c.dateFin >= :debut')
            ->setParameter('t', $typePersonne)
            ->setParameter('p', $personneId)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin);

        if ($exclureId !== null) {
            $qb->andWhere('c.id != :ex')->setParameter('ex', $exclureId);
        }
        if ($statuts !== null) {
            $qb->andWhere('c.statut IN (:st)')->setParameter('st', $statuts);
        }

        return $qb->getQuery()->getResult();
    }

    /** Un congé actif appartenant bien à cette personne (contrôle d'accès). */
    public function findUnActifPour(int $id, string $typePersonne, string $personneId): ?Conge
    {
        return $this->createQueryBuilder('c')
            ->where('c.id = :id')
            ->andWhere('c.typePersonne = :t')
            ->andWhere('c.personneId = :p')
            ->andWhere('c.annuleLe IS NULL')
            ->setParameter('id', $id)
            ->setParameter('t', $typePersonne)
            ->setParameter('p', $personneId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Tous les congés actifs (non annulés) qui chevauchent une période, pour un
     * type de personne. Sert aux vues admin (planning, remplacements).
     *
     * @return Conge[]
     */
    public function findActifsSurPeriode(string $typePersonne, \DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.typePersonne = :t')
            ->andWhere('c.annuleLe IS NULL')
            ->andWhere('c.dateDebut <= :fin')
            ->andWhere('c.dateFin >= :debut')
            ->setParameter('t', $typePersonne)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('c.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tous les congés actifs (non annulés), les plus récents d'abord.
     * Filtrable par type de personne. Sert à la vue admin « Congés ».
     *
     * @return Conge[]
     */
    public function findActifs(?string $typePersonne = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.annuleLe IS NULL')
            ->orderBy('c.dateDebut', 'DESC');

        if ($typePersonne !== null) {
            $qb->andWhere('c.typePersonne = :t')->setParameter('t', $typePersonne);
        }

        return $qb->getQuery()->getResult();
    }

    public function save(Conge $conge, bool $flush = true): void
    {
        $this->getEntityManager()->persist($conge);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
