<?php

namespace App\Repository;

use App\Entity\Horaire\AppConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppConfig>
 */
class AppConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppConfig::class);
    }

    /** Retourne la config (id=1), ou la crée avec les valeurs par défaut. */
    public function getConfig(): AppConfig
    {
        $config = $this->find(1);
        if (!$config) {
            $config = new AppConfig();
            $this->getEntityManager()->persist($config);
            $this->getEntityManager()->flush();
        }
        return $config;
    }
}
