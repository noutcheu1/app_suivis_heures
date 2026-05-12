<?php

namespace App\Repository;

use App\Entity\Principal\Proposer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Proposer>
 */
class ProposerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Proposer::class);
    }

    /**
     * Assignations actives d'un intervenant (dateFin null ou >= aujourd'hui).
     *
     * @return Proposer[]
     */
    public function findActivesByIntervenant(int $numSalarie): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.numSalarie = :id')
            ->andWhere('p.dateFin IS NULL OR p.dateFin >= :today')
            ->setParameter('id', $numSalarie)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('p.jour')
            ->addOrderBy('p.heureDebut')
            ->getQuery()
            ->getResult();
    }

    /**
     * Assignations actives d'une famille.
     *
     * @return Proposer[]
     */
    public function findActivesByFamille(string $numeroFamille): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.numeroFamille = :num')
            ->andWhere('p.dateFin IS NULL OR p.dateFin >= :today')
            ->setParameter('num', $numeroFamille)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('p.jour')
            ->addOrderBy('p.heureDebut')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie qu'un intervenant est actif chez une famille.
     */
    public function isIntervenantAssignedToFamille(int $numSalarie, string $numeroFamille): bool
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.numSalarie)')
            ->where('p.numSalarie = :id')
            ->andWhere('p.numeroFamille = :num')
            ->andWhere('p.dateFin IS NULL OR p.dateFin >= :today')
            ->setParameter('id', $numSalarie)
            ->setParameter('num', $numeroFamille)
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$count > 0;
    }

    /**
     * Numéros de famille distincts liés à un intervenant (actifs).
     *
     * @return string[]
     */
    public function findNumerosFamilleByIntervenant(int $numSalarie): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('DISTINCT p.numeroFamille')
            ->where('p.numSalarie = :id')
            ->andWhere('p.dateFin IS NULL OR p.dateFin >= :today')
            ->setParameter('id', $numSalarie)
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'numeroFamille');
    }

    /**
     * numSalarie distincts liés à une famille (actifs).
     *
     * @return int[]
     */
    public function findNumsSalarieByFamille(string $numeroFamille): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('DISTINCT p.numSalarie')
            ->where('p.numeroFamille = :num')
            ->andWhere('p.dateFin IS NULL OR p.dateFin >= :today')
            ->setParameter('num', $numeroFamille)
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getScalarResult();

        return array_map('intval', array_column($rows, 'numSalarie'));
    }

    /**
     * Prochain créneau de la semaine pour un intervenant.
     * Cherche d'abord aujourd'hui (heure >= maintenant) puis les jours suivants.
     *
     * @return Proposer|null
     */
    public function findNextSlot(int $numSalarie): ?Proposer
    {
        $joursOrdonnes = $this->getJoursDepuisAujourdhui();
        if (empty($joursOrdonnes)) {
            return null;
        }

        $today = array_key_first($joursOrdonnes);
        $now   = new \DateTime();

        // Cherche un créneau encore à venir aujourd'hui
        $slot = $this->createQueryBuilder('p')
            ->where('p.numSalarie = :id')
            ->andWhere('p.jour = :jour')
            ->andWhere('p.heureDebut >= :now')
            ->andWhere('p.dateFin IS NULL OR p.dateFin >= :today')
            ->setParameter('id', $numSalarie)
            ->setParameter('jour', $today)
            ->setParameter('now', $now)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('p.heureDebut')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($slot) {
            return $slot;
        }

        // Sinon, premier créneau dans les 6 prochains jours
        $prochains = array_slice(array_keys($joursOrdonnes), 1);
        foreach ($prochains as $jour) {
            $slot = $this->createQueryBuilder('p')
                ->where('p.numSalarie = :id')
                ->andWhere('p.jour = :jour')
                ->andWhere('p.dateFin IS NULL OR p.dateFin >= :today')
                ->setParameter('id', $numSalarie)
                ->setParameter('jour', $jour)
                ->setParameter('today', new \DateTime('today'))
                ->orderBy('p.heureDebut')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($slot) {
                return $slot;
            }
        }

        return null;
    }

    /** @return array<string, int> jour => index, ordonné depuis aujourd'hui */
    private function getJoursDepuisAujourdhui(): array
    {
        $map = [
            'Monday'    => 'lundi',
            'Tuesday'   => 'mardi',
            'Wednesday' => 'mercredi',
            'Thursday'  => 'jeudi',
            'Friday'    => 'vendredi',
            'Saturday'  => 'samedi',
            'Sunday'    => 'dimanche',
        ];
        $ordre  = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $today  = $map[(new \DateTime())->format('l')] ?? 'lundi';
        $idx    = array_search($today, $ordre, true);
        $rotated = array_merge(
            array_slice($ordre, $idx),
            array_slice($ordre, 0, $idx)
        );

        return array_flip($rotated);
    }
}
