<?php

namespace App\Repository;

use App\Entity\Horaire\VacancesReponseFamille;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VacancesReponseFamille>
 */
class VacancesReponseFamilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VacancesReponseFamille::class);
    }

    public function findByToken(string $token): ?VacancesReponseFamille
    {
        return $this->findOneBy(['token' => $token]);
    }

    /** @return VacancesReponseFamille[] */
    public function findByConfig(int $configId): array
    {
        return $this->findBy(['vacancesConfigId' => $configId], ['createdAt' => 'ASC']);
    }

    /** Dernière réponse (toutes campagnes) d'une famille, ou null. */
    public function findLatestByNumFam(string $numFam): ?VacancesReponseFamille
    {
        return $this->findOneBy(['numFam' => $numFam], ['createdAt' => 'DESC']);
    }

    public function existsPour(int $configId, string $numFam): bool
    {
        return (bool) $this->findOneBy(['vacancesConfigId' => $configId, 'numFam' => $numFam]);
    }

    public function save(VacancesReponseFamille $r, bool $flush = true): void
    {
        $this->getEntityManager()->persist($r);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
