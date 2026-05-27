<?php

namespace App\Repository;

use App\Entity\Horaire\Relevemensuelfam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Relevemensuelfam>
 *
 * @method Relevemensuelfam|null find($id, $lockMode = null, $lockVersion = null)
 * @method Relevemensuelfam|null findOneBy(array $criteria, array $orderBy = null)
 * @method Relevemensuelfam[]    findAll()
 * @method Relevemensuelfam[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RelevemensuelfamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Relevemensuelfam::class);
    }

    /**
     * Retourne les relevés d'une famille
     */
    public function findByFamille(string $numFam): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.numFam = :numFam')
            ->setParameter('numFam', $numFam)
            ->orderBy('r.numFam', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne un relevé spécifique
     */
    public function findByMoisAnneeFamille(string $moisAnnee, string $numFam, string $typePresta): ?Relevemensuelfam
    {
        return $this->createQueryBuilder('r')
            ->where('r.moisannee = :moisAnnee')
            ->andWhere('r.numFam = :numFam')
            ->andWhere('r.typePresta = :typePresta')
            ->setParameter('moisAnnee', $moisAnnee)
            ->setParameter('numFam', $numFam)
            ->setParameter('typePresta', $typePresta)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les relevés non signés d'une famille
     */
    public function findNonSignesByFamille(string $numFam): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.numFam = :numFam')
            ->andWhere('r.signerLe IS NULL')
            ->setParameter('numFam', $numFam)
            ->orderBy('r.numFam', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le montant total d'un relevé
     */
    public function calculerMontantTotal(string $moisAnnee, string $numFam, string $typePresta): float
    {
        $releve = $this->findByMoisAnneeFamille($moisAnnee, $numFam, $typePresta);
        
        if (!$releve) {
            return 0.0;
        }

        $montant = 0.0;
        
        if ($releve->getMontantPrincipal()) {
            $montant += (float) $releve->getMontantPrincipal();
        }
        
        if ($releve->getMontantComplement()) {
            $montant += (float) $releve->getMontantComplement();
        }
        
        if ($releve->getMontantSupl()) {
            $montant += (float) $releve->getMontantSupl();
        }

        return $montant;
    }

    /**
     * Signe un relevé familial
     */
    public function signerReleve(string $moisAnnee, string $numFam, string $typePresta): bool
    {
        $releve = $this->findByMoisAnneeFamille($moisAnnee, $numFam, $typePresta);

        if (!$releve) {
            return false;
        }

        $releve->setSignerLe(new \DateTime());

        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Tous les relevés ayant une exception de facturation (libelerSupl non nul).
     */
    public function findAvecException(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.libelerSupl IS NOT NULL')
            ->andWhere("r.libelerSupl != ''")
            ->orderBy('r.moisannee', 'DESC')
            ->addOrderBy('r.numFam', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Exceptions de facturation d'une famille.
     */
    public function findExceptionsByFamille(string $numFam): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.numFam = :numFam')
            ->andWhere('r.libelerSupl IS NOT NULL')
            ->andWhere("r.libelerSupl != ''")
            ->setParameter('numFam', $numFam)
            ->orderBy('r.moisannee', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
