<?php

namespace App\Repository;

use App\Entity\Horaire\VacancesReponseIntervenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VacancesReponseIntervenant>
 */
class VacancesReponseIntervenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VacancesReponseIntervenant::class);
    }

    public function findByToken(string $token): ?VacancesReponseIntervenant
    {
        return $this->findOneBy(['token' => $token]);
    }

    /** @return VacancesReponseIntervenant[] */
    public function findByConfig(int $configId): array
    {
        return $this->findBy(['vacancesConfigId' => $configId], ['createdAt' => 'ASC']);
    }

    /** Dernière réponse (toutes campagnes) d'un intervenant, ou null. */
    public function findLatestByNumInter(int $numInter): ?VacancesReponseIntervenant
    {
        return $this->findOneBy(['numInter' => $numInter], ['createdAt' => 'DESC']);
    }

    public function existsPour(int $configId, int $numInter): bool
    {
        return (bool) $this->findOneBy(['vacancesConfigId' => $configId, 'numInter' => $numInter]);
    }

    public function save(VacancesReponseIntervenant $r, bool $flush = true): void
    {
        $this->getEntityManager()->persist($r);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
