<?php

namespace App\Repository;

use App\Entity\Horaire\VacancesConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VacancesConfig>
 */
class VacancesConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VacancesConfig::class);
    }

    /** Retourne la config de vacances active dont la fenêtre de dates est valide, ou null. */
    public function findActif(): ?VacancesConfig
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('v')
            ->where('v.actif = :actif')
            ->andWhere('v.dateApparitionDebut IS NULL OR v.dateApparitionDebut <= :today')
            ->andWhere('v.dateApparitionFin IS NULL OR v.dateApparitionFin >= :today')
            ->setParameter('actif', true)
            ->setParameter('today', $today)
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Campagnes à ENVOYER : statut brouillon et date d'ouverture atteinte
     * (ouverture NULL = envoi possible tout de suite).
     *
     * @return VacancesConfig[]
     */
    public function findAEnvoyer(\DateTimeImmutable $today): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.statut = :brouillon')
            ->andWhere('v.dateApparitionDebut IS NULL OR v.dateApparitionDebut <= :today')
            ->setParameter('brouillon', VacancesConfig::STATUT_BROUILLON)
            ->setParameter('today', $today)
            ->orderBy('v.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Campagne « pertinente » pour la fiche famille : la plus récente qui est
     * envoyée (collecte en cours) OU clôturée depuis peu (fenêtre de travail
     * de 5 jours). Sert à n'afficher qu'UNE campagne, celle sur laquelle on agit.
     */
    public function findPourFicheFamille(): ?VacancesConfig
    {
        $limite = new \DateTimeImmutable('today -5 days');

        return $this->createQueryBuilder('v')
            ->where('v.statut = :env OR (v.statut = :clo AND v.dateLimiteReponse >= :limite)')
            ->setParameter('env', VacancesConfig::STATUT_ENVOYEE)
            ->setParameter('clo', VacancesConfig::STATUT_CLOTUREE)
            ->setParameter('limite', $limite)
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Toutes les configs, les plus récentes en premier. */
    public function findAllOrderedDesc(): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Désactive toutes les configs (avant d'en activer une nouvelle). */
    public function desactiverTout(): void
    {
        $this->createQueryBuilder('v')
            ->update()
            ->set('v.actif', ':false')
            ->setParameter('false', false)
            ->getQuery()
            ->execute();
    }
}
