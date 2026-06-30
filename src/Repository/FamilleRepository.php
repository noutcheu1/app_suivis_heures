<?php

namespace App\Repository;

use App\Entity\Principal\Famille;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Famille>
 *
 * @method Famille|null find($id, $lockMode = null, $lockVersion = null)
 * @method Famille|null findOneBy(array $criteria, array $orderBy = null)
 * @method Famille[]    findAll()
 * @method Famille[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FamilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Famille::class);
    }

    // -------------------------------------------------------------------------
    // Méthodes privées utilitaires
    // -------------------------------------------------------------------------

    /**
     * Applique les conditions communes : non archivée et numéro != 9999.
     * Centralise la logique répétée dans toutes les requêtes DQL.
     */
    private function addBaseConditions(QueryBuilder $qb, string $alias = 'f'): QueryBuilder
    {
        return $qb
            ->andWhere("($alias.archive = :archive OR $alias.archive IS NULL)")
            ->andWhere("$alias.numeroFamille != 9999")
            ->setParameter('archive', 0);
    }

    /**
     * Restreint aux familles prestataires : non mandataires (mand_Famille = 0)
     * ET ayant un numéro PGE ou PM renseigné.
     */
    private function addPrestataireFamilleCondition(QueryBuilder $qb, string $alias = 'f'): QueryBuilder
    {
        return $qb
            ->andWhere("($alias.mandataire = :notMand OR $alias.mandataire IS NULL)")
            ->andWhere(
                "($alias.pgeFamille IS NOT NULL AND $alias.pgeFamille != :emptyPGEPM)"
                . " OR ($alias.pmFamille IS NOT NULL AND $alias.pmFamille != :emptyPGEPM)"
            )
            ->setParameter('notMand', false)
            ->setParameter('emptyPGEPM', '');
    }
    public function getVilleFamille($numFam){
        $conn = $this->getEntityManager()->getConnection();

        return $conn->fetchOne(
            'SELECT ville
             FROM famille
             WHERE numeroFamille = :numFam',
            ['numFam' => $numFam]
        );
    }
    public function getKmHeure($numFam)
    {
        $conn = $this->getEntityManager()->getConnection();

        return (float) $conn->fetchOne(
            'SELECT kmAvecEnfant
             FROM famille
             WHERE numeroFamille = :numFam',
            ['numFam' => $numFam]
        );
    
    }
    /**
     * Calcule la période du 25 du mois précédent au 24 du mois en cours.
     *
     * @return array{debutPeriode: string, finPeriode: string}
     */
    private function getPeriodeCourante(): array
    {
        $now   = new \DateTimeImmutable();
        $annee = (int) $now->format('Y');
        $mois  = (int) $now->format('m');

        // 25 du mois précédent
        $moisPrec    = $mois === 1 ? 12 : $mois - 1;
        $anneePrec   = $mois === 1 ? $annee - 1 : $annee;
        $debutPeriode = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-25', $anneePrec, $moisPrec));

        // 24 du mois en cours
        $finPeriode = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-24', $annee, $mois));

        return [
            'debutPeriode' => $debutPeriode->format('Y-m-d'),
            'finPeriode'   => $finPeriode->format('Y-m-d'),
        ];
    }

    // -------------------------------------------------------------------------
    // Requêtes publiques
    // -------------------------------------------------------------------------

    /**
     * Retourne toutes les familles non archivées, excluant le numéro 9999.
     *
     * @return Famille[]
     */
    public function findAllNonArchived(): array
    {
        $qb = $this->createQueryBuilder('f')
            ->orderBy('f.nomFamille', 'ASC');

        $this->addBaseConditions($qb);
        $this->addPrestataireFamilleCondition($qb);

        return $qb->getQuery()->getResult();
    }


    public function findFamilleGardeNonArchive(): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.prestGardeEnfants = 1')
            ->orderBy('f.nomFamille', 'ASC');

        $this->addBaseConditions($qb);
        $this->addPrestataireFamilleCondition($qb);

        return $qb->getQuery()->getResult();
    }

    public function findFamilleMenageNonArchive(): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.prestMenage = 1')
            ->orderBy('f.nomFamille', 'ASC');

        $this->addBaseConditions($qb);
        $this->addPrestataireFamilleCondition($qb);

        return $qb->getQuery()->getResult();
    }
    /**
     * Retourne une famille par son numéro, non archivée et non factice.
     */
    // public function findByNumero(string $numero): ?Famille
    // {
    //     $qb = $this->createQueryBuilder('f')
    //         ->andWhere('f.numeroFamille = :numero')
    //         ->setParameter('numero', $numero);

    //     $this->addBaseConditions($qb);

    //     return $qb->getQuery()->getOneOrNullResult();
    // }

    public function findByNumero(string $numero): ?Famille
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.numeroFamille = :numero OR f.pgeFamille = :numero OR f.pmFamille = :numero')
            ->setParameter('numero', $numero);

        $this->addBaseConditions($qb);
        $this->addPrestataireFamilleCondition($qb);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Recherche des familles par nom ou ville (non archivées, excluant 9999).
     *
     * CORRECTIF : parenthèses autour du OR pour éviter un bug de priorité
     * d'opérateurs avec les andWhere() suivants.
     *
     * @return Famille[]
     */
    public function findByNomOrVille(string $search): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('(f.nomFamille LIKE :search OR f.ville LIKE :search)')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('f.nomFamille', 'ASC');

        $this->addBaseConditions($qb);
        $this->addPrestataireFamilleCondition($qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les familles par ville (non archivées, excluant 9999).
     *
     * @return Famille[]
     */
    public function findByVille(string $ville): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.ville = :ville')
            ->setParameter('ville', $ville)
            ->orderBy('f.nomFamille', 'ASC');

        $this->addBaseConditions($qb);
        $this->addPrestataireFamilleCondition($qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de familles actives (non archivées, excluant 9999).
     */
    public function countActives(): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.numeroFamille)');

        $this->addBaseConditions($qb);
        $this->addPrestataireFamilleCondition($qb);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Compte les familles ayant un planning actif dans proposer (excluant 9999).
     *
     * CORRECTIF : noms de colonnes harmonisés en camelCase (numeroFamille,
     * archive_Famille) conformément au reste du fichier.
     */
    public function countAvecPlanningActif(): int
    {
        $conn = $this->getEntityManager()->getConnection();

        return (int) $conn->fetchOne(
            'SELECT COUNT(DISTINCT f.numero_Famille)
             FROM famille f
             INNER JOIN proposer p ON p.numero_Famille = f.numero_Famille
             WHERE (f.archive_Famille = 0 OR f.archive_Famille IS NULL)
               AND f.numero_Famille != 9999
               AND (f.mand_Famille = 0 OR f.mand_Famille IS NULL)
               AND ((f.PGE_Famille IS NOT NULL AND f.PGE_Famille != \'\')
                    OR (f.PM_Famille IS NOT NULL AND f.PM_Famille != \'\'))
               AND (p.dateFin_Proposer IS NULL
                    OR p.dateFin_Proposer = "0000-00-00"
                    OR p.dateFin_Proposer >= CURDATE())'
        );
    }

    /**
     * Familles non archivées ayant au moins une assignation active dans `proposer`.
     * Exclut le numéro 9999.
     *
     * @return Famille[]
     */
    public function findWithActivePlanning(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $numeros = $conn->fetchFirstColumn(
            "SELECT DISTINCT f.numero_Famille
             FROM famille f
             INNER JOIN proposer p ON p.numero_Famille = f.numero_Famille
             INNER JOIN intervenants i ON i.numSalarie_Intervenants = p.numSalarie_Intervenants
             WHERE (f.archive_Famille = 0 OR f.archive_Famille IS NULL)
               AND f.numero_Famille != '9999'
               AND (f.dateEntree_Famille IS NULL OR f.dateEntree_Famille <= CURDATE())
               AND (f.dateSortie_Famille IS NULL OR f.dateSortie_Famille = '0000-00-00' OR f.dateSortie_Famille >= CURDATE())
               AND (i.archive_Intervenants = 0 OR i.archive_Intervenants IS NULL)
               AND (i.dateEntree_Intervenants IS NULL OR i.dateEntree_Intervenants <= CURDATE())
               AND (i.dateSortie_Intervenants IS NULL OR i.dateSortie_Intervenants = '0000-00-00' OR i.dateSortie_Intervenants >= CURDATE())
               AND (f.mand_Famille = 0 OR f.mand_Famille IS NULL)
               AND ((f.PGE_Famille IS NOT NULL AND f.PGE_Famille != '')
                    OR (f.PM_Famille IS NOT NULL AND f.PM_Famille != ''))
               AND p.idADH_TypeADH = 'PREST'
               AND (p.idPresta_Prestations = 'MENA' OR p.idPresta_Prestations = 'ENFA')
               AND (p.dateFin_Proposer IS NULL
                    OR p.dateFin_Proposer = '0000-00-00'
                    OR p.dateFin_Proposer >= CURDATE())
             ORDER BY f.numero_Famille ASC"
        );

        if (empty($numeros)) {
            return [];
        }

        return $this->createQueryBuilder('f')
            ->where('f.numero_Famille IN (:numeros)')
            ->setParameter('numeros', $numeros)
            ->orderBy('f.nomFamille', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Familles ayant un planning actif PENDANT le mois sélectionné.
     * Contrairement à findWithActivePlanning(), ne compare pas à CURDATE()
     * utile pour les mois passés ou futurs. Exclut 9999.
     *
     * @return Famille[]
     */
    public function findWithPlanningForMonth(int $year, int $month): array
    {
        $firstDay = \DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', $year, $month));
        $lastDay  = $firstDay->modify('last day of this month');

        $conn    = $this->getEntityManager()->getConnection();
        $numeros = $conn->fetchFirstColumn(
            'SELECT DISTINCT f.numero_Famille
             FROM famille f
             INNER JOIN proposer p ON p.numero_Famille = f.numero_Famille
             INNER JOIN intervenants i ON i.numSalarie_Intervenants = p.numSalarie_Intervenants
             WHERE (f.archive_Famille = 0 OR f.archive_Famille IS NULL)
               AND f.numero_Famille != 9999
               AND (f.mand_Famille = 0 OR f.mand_Famille IS NULL)
               AND ((f.PGE_Famille IS NOT NULL AND f.PGE_Famille != \'\')
                    OR (f.PM_Famille IS NOT NULL AND f.PM_Famille != \'\'))
               AND (f.dateEntree_Famille IS NULL OR f.dateEntree_Famille <= :last)
               AND (f.dateSortie_Famille IS NULL
                    OR f.dateSortie_Famille = "0000-00-00"
                    OR f.dateSortie_Famille >= :first)
               AND (i.archive_Intervenants = 0 OR i.archive_Intervenants IS NULL)
               AND (i.dateEntree_Intervenants IS NULL OR i.dateEntree_Intervenants <= :last)
               AND (i.dateSortie_Intervenants IS NULL
                    OR i.dateSortie_Intervenants = "0000-00-00"
                    OR i.dateSortie_Intervenants >= :first)
               AND p.dateDeb_Proposer <= :last
               AND (p.dateFin_Proposer IS NULL
                    OR p.dateFin_Proposer = "0000-00-00"
                    OR p.dateFin_Proposer >= :first)
               ',
            [
                'first' => $firstDay->format('Y-m-d'),
                'last'  => $lastDay->format('Y-m-d'),
            ]
        );

        if (empty($numeros)) {
            return [];
        }

        return $this->createQueryBuilder('f')
            ->where('f.numeroFamille IN (:numeros)')
            ->setParameter('numeros', $numeros)
            ->orderBy('f.nomFamille', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Familles assignées à un intervenant précis via proposer (actif).
     * Période du 25 du mois précédent au 24 du mois en cours.
     * Exclut le numéro 9999.
     *
     * CORRECTIF : noms de colonnes harmonisés (numeroFamille, archive_Famille)
     * et calcul de dates refactorisé via getPeriodeCourante().
     *
     * @return Famille[]
     */
    public function findByIntervenantActif(int $numSalarie): array
    {
        $periode = $this->getPeriodeCourante();
        $conn    = $this->getEntityManager()->getConnection();
 
        $numeros = $conn->fetchFirstColumn(
            'SELECT DISTINCT p.numero_Famille
             FROM proposer p
             INNER JOIN famille f ON f.numero_Famille = p.numero_Famille
             WHERE p.numSalarie_Intervenants = :numSalarie
               AND p.idADH_TypeADH = \'PREST\'
               AND (p.idPresta_Prestations = \'MENA\' OR p.idPresta_Prestations = \'ENFA\')
               AND (f.archive_Famille = 0 OR f.archive_Famille IS NULL)
               AND f.numero_Famille != 9999
               AND (f.mand_Famille = 0 OR f.mand_Famille IS NULL)
               AND ((f.PGE_Famille IS NOT NULL AND f.PGE_Famille != \'\')
                    OR (f.PM_Famille IS NOT NULL AND f.PM_Famille != \'\'))',
            ['numSalarie' => $numSalarie]
        );
 
        if (empty($numeros)) {
            return [];
        }
 
        return $this->createQueryBuilder('f')
            ->where('f.numeroFamille IN (:numeros)')
            ->setParameter('numeros', $numeros)
            ->orderBy('f.nomFamille', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne tous les numéros de familles prestataires actives (non mandataires + PGE/PM).
     * Utilisé pour filtrer les stats globales (ex. page admin relevés).
     *
     * @return string[]
     */
    public function findAllValidFamilleIds(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        return $conn->fetchFirstColumn(
            "SELECT numero_Famille
             FROM famille
             WHERE (archive_Famille = 0 OR archive_Famille IS NULL)
               AND numero_Famille != '9999'
               AND (mand_Famille = 0 OR mand_Famille IS NULL)
               AND ((PGE_Famille IS NOT NULL AND PGE_Famille != '')
                    OR (PM_Famille IS NOT NULL AND PM_Famille != ''))"
        );
    }

    /**
     * Retourne, parmi une liste de numéros de famille, ceux qui sont prestataires
     * (non mandataires ET ayant un PGE ou PM). Utilisé pour filtrer les prestations
     * dans les relevés et exclure les familles mandataires.
     *
     * @param string[] $numFams
     * @return string[]
     */
    public function findValidFamilleIds(array $numFams): array
    {
        if (empty($numFams)) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        return $conn->fetchFirstColumn(
            'SELECT numero_Famille
             FROM famille
             WHERE numero_Famille IN (:numFams)
               AND (mand_Famille = 0 OR mand_Famille IS NULL)
               AND ((PGE_Famille IS NOT NULL AND PGE_Famille != \'\')
                    OR (PM_Famille IS NOT NULL AND PM_Famille != \'\'))',
            ['numFams' => $numFams],
            ['numFams' => \Doctrine\DBAL\ArrayParameterType::STRING]
        );
    }
}