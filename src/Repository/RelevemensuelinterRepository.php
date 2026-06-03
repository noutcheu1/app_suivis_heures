<?php

namespace App\Repository;

use App\Entity\Horaire\Relevemensuelinter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Relevemensuelinter>
 *
 * @method Relevemensuelinter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Relevemensuelinter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Relevemensuelinter[]    findAll()
 * @method Relevemensuelinter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RelevemensuelinterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Relevemensuelinter::class);
    }

    /**
     * Retourne les relevés d'un intervenant
     */
    public function findByIntervenant(int $numInter): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.numInter = :numInter')
            ->setParameter('numInter', $numInter)
            ->orderBy('r.moisannee', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne un relevé spécifique
     */
    public function findByMoisAnneeIntervenant(string $moisAnnee, int $numInter, string $typePresta): ?Relevemensuelinter
    {
        return $this->createQueryBuilder('r')
            ->where('r.moisannee = :moisAnnee')
            ->andWhere('r.numInter = :numInter')
            ->andWhere('r.typePresta = :typePresta')
            ->setParameter('moisAnnee', $moisAnnee)
            ->setParameter('numInter', $numInter)
            ->setParameter('typePresta', $typePresta)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les relevés à signer pour un intervenant
     */
    public function findNonSignesByIntervenant(int $numInter): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.numInter = :numInter')
            ->andWhere('r.signer = :signer')
            ->setParameter('numInter', $numInter)
            ->setParameter('signer', false)
            ->orderBy('r.moisannee', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Signe un relevé, le crée s'il n'existe pas encore
     */
    public function signerReleve(string $moisAnnee, int $numInter, string $typePresta): Relevemensuelinter
    {
        $releve = $this->findOrCreate($moisAnnee, $numInter, $typePresta);

        $releve->setSigner(true);
        $releve->setSignerLe(new \DateTime());

        $this->getEntityManager()->flush();

        return $releve;
    }

    /**
     * Enregistre les heures travaillées hors structure pour un mois
     */
    public function ajouterHeureDehors(string $moisAnnee, int $numInter, string $typePresta, \DateTimeInterface $heureDehors): Relevemensuelinter
    {
        $releve = $this->findOrCreate($moisAnnee, $numInter, $typePresta);

        $releve->setHeureDehors($heureDehors);
        $releve->setHeureDehorsAjouterLe(new \DateTime());

        $this->getEntityManager()->flush();

        return $releve;
    }

    public function marquerTelecharge(string $moisAnnee, int $numInter, string $typePresta): void
    {
        $releve = $this->findOrCreate($moisAnnee, $numInter, $typePresta);
        $releve->setTelechargerLe(new \DateTime());
        $this->getEntityManager()->flush();
    }

    private function findOrCreate(string $moisAnnee, int $numInter, string $typePresta): Relevemensuelinter
    {
        $releve = $this->findByMoisAnneeIntervenant($moisAnnee, $numInter, $typePresta);

        if (!$releve) {
            $releve = new Relevemensuelinter();
            $releve->setMoisannee($moisAnnee);
            $releve->setNumInter($numInter);
            $releve->setTypePresta($typePresta);
            $this->getEntityManager()->persist($releve);
        }

        return $releve;
    }
}
