<?php

namespace App\Repository;

use App\Entity\Horaire\TarifFamille;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TarifFamille>
 */
class TarifFamilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TarifFamille::class);
    }

    /**
     * Taux horaire actif pour une famille + type de prestation à un mois donné.
     * Applique la même logique que tarifs_suivi : le plus récent dont dateDebut <= mois.
     */
    public function findActif(string $numFam, string $typePresta, ?string $moisAnnee = null): ?TarifFamille
    {
        $mois = $moisAnnee ?? date('Y-m');

        return $this->createQueryBuilder('t')
            ->where('t.numFam = :numFam')
            ->andWhere('t.typePresta = :type')
            ->andWhere('t.dateDebut <= :mois')
            ->setParameter('numFam', $numFam)
            ->setParameter('type', $typePresta)
            ->setParameter('mois', $mois)
            ->orderBy('t.dateDebut', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Toutes les versions tarifaires d'une famille, ordre chronologique décroissant.
     */
    public function findByFamille(string $numFam): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.numFam = :numFam')
            ->setParameter('numFam', $numFam)
            ->orderBy('t.dateDebut', 'DESC')
            ->addOrderBy('t.typePresta', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Derniers tarifs actifs par famille (une ligne par famille × type).
     * Utilisé pour la vue globale admin.
     */
    public function findTousActifs(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $ids = $conn->fetchFirstColumn(
            'SELECT t1.id
             FROM tauxhoraire t1
             INNER JOIN (
                 SELECT numFam, typePresta, MAX(dateDebut) AS maxDate
                 FROM tauxhoraire
                 GROUP BY numFam, typePresta
             ) t2 ON t1.numFam = t2.numFam
                  AND t1.typePresta = t2.typePresta
                  AND t1.dateDebut = t2.maxDate
             INNER JOIN (
                 SELECT numFam, typePresta, dateDebut, MAX(id) AS maxId
                 FROM tauxhoraire
                 GROUP BY numFam, typePresta, dateDebut
             ) t3 ON t1.numFam = t3.numFam
                  AND t1.typePresta = t3.typePresta
                  AND t1.dateDebut = t3.dateDebut
                  AND t1.id = t3.maxId'
        );

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('t')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', array_map('intval', $ids))
            ->orderBy('t.numFam', 'ASC')
            ->addOrderBy('t.typePresta', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
