<?php

namespace App\Repository;

use App\Entity\Principal\Intervenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Intervenant>
 *
 * @method Intervenant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Intervenant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Intervenant[]    findAll()
 * @method Intervenant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IntervenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Intervenant::class);
    }

    // -------------------------------------------------------------------------
    // Méthode privée partagée
    // -------------------------------------------------------------------------

    /**
     * Retourne les IDs (int[]) des intervenants ayant au moins une ligne active
     * dans `proposer` avec idADH_TypeADH = 'PREST'.
     *
     * Toutes les méthodes publiques du repository passent par ici pour garantir
     * qu'on ne manipule jamais un intervenant sans proposer PREST actif.
     *
     * @return int[]
     */
    private function getActiveProposerIds(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $ids = $conn->fetchFirstColumn(
            "SELECT DISTINCT p.numSalarie_Intervenants
             FROM proposer p
             INNER JOIN famille f ON f.numero_Famille = p.numero_Famille
             WHERE p.idADH_TypeADH = 'PREST'
               AND (p.idPresta_Prestations = 'MENA' OR p.idPresta_Prestations = 'ENFA')
               AND (p.dateFin_Proposer IS NULL
                    OR p.dateFin_Proposer = '0000-00-00'
                    OR p.dateFin_Proposer >= CURDATE())
               AND (f.mand_Famille = 0 OR f.mand_Famille IS NULL)
               AND ((f.PGE_Famille IS NOT NULL AND f.PGE_Famille != '')
                    OR (f.PM_Famille IS NOT NULL AND f.PM_Famille != ''))"
        );

        return array_map('intval', $ids);
    }

    // -------------------------------------------------------------------------
    // Méthodes publiques
    // -------------------------------------------------------------------------

    public function findAllNonArchived(): array
    {
        $ids = $this->getActiveProposerIds();
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('i')
            ->where('i.numSalarie_Intervenants IN (:ids)')
            ->andWhere('i.archive = :archive')
            ->setParameter('ids', $ids)
            ->setParameter('archive', 0)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findInfosIntervenant(int $id): ?Intervenant
    {
        $ids = $this->getActiveProposerIds();
        if (empty($ids) || !in_array($id, $ids, true)) {
            return null;
        }

        return $this->createQueryBuilder('i')
            ->where('i.numSalarie_Intervenants = :id')
            ->andWhere('i.numSalarie_Intervenants IN (:ids)')
            ->andWhere('i.archive = :archive')
            ->setParameter('id', $id)
            ->setParameter('ids', $ids)
            ->setParameter('archive', 0)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByNumSs(string $numSs): ?Intervenant
    {
        $ids = $this->getActiveProposerIds();
        if (empty($ids)) {
            return null;
        }

        return $this->createQueryBuilder('i')
            ->where('i.numSs = :numSs')
            ->andWhere('i.archive = :archive')
            ->andWhere('i.numSalarie_Intervenants IN (:ids)')
            ->setParameter('numSs', $numSs)
            ->setParameter('archive', 0)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByNumSalarie(string $numSalarie): ?Intervenant
    {
        $ids = $this->getActiveProposerIds();
        if (empty($ids)) {
            return null;
        }

        return $this->createQueryBuilder('i')
            ->where('i.numSalarie = :numSalarie')
            ->andWhere('i.archive = :archive')
            ->andWhere('i.numSalarie_Intervenants IN (:ids)')
            ->setParameter('numSalarie', $numSalarie)
            ->setParameter('archive', 0)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Résout un intervenant par son numéro de téléphone (portable), normalisé.
     *
     * Sert au pointage QR sans connexion : l'intervenant saisit son numéro, on
     * retrouve son dossier via candidats.telPortable_Candidats (exposé par la vue
     * vue_intervenants). Le numéro stocké ("06.12.34.56.78") est nettoyé en SQL
     * des mêmes séparateurs que UserSuiviRepository::normaliserTel().
     *
     * Retourne null si introuvable OU ambigu (>1 intervenant actif avec ce numéro).
     */
    public function findByTelephoneNormalise(string $tel): ?Intervenant
    {
        $norm = UserSuiviRepository::normaliserTel($tel);
        if ($norm === '') {
            return null;
        }

        $ids = $this->getActiveProposerIds();
        if (empty($ids)) {
            return null;
        }

        $conn = $this->getEntityManager()->getConnection();
        $matches = $conn->fetchFirstColumn(
            "SELECT numSalarie_Intervenants
             FROM vue_intervenants
             WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                       COALESCE(telPortable_Candidats, ''),
                       '.',''),' ',''),'-',''),'/',''),'(',''),')','') = :norm",
            ['norm' => $norm]
        );

        // On ne garde que les intervenants ayant un proposer PREST actif (non archivés).
        $matches = array_values(array_intersect(
            array_map('intval', $matches),
            $ids
        ));

        if (count($matches) !== 1) {
            return null; // 0 = introuvable, >1 = ambigu → on refuse
        }

        return $this->findInfosIntervenant($matches[0]);
    }

    /**
     * Trouve l'intervenant lié à un candidat via la FK
     * intervenants.candidats_numcandidat_candidats = $candidatId.
     *
     * On interroge la table intervenants directement (pas la vue)
     * pour obtenir le numSalarie_Intervenants, puis on charge l'entité
     * uniquement si elle possède un proposer PREST actif.
     */
    public function findByCandidatId(int $candidatId): ?Intervenant
    {
        $conn = $this->getEntityManager()->getConnection();

        $numSalarie = $conn->fetchOne(
            'SELECT numSalarie_Intervenants
             FROM intervenants
             WHERE candidats_numcandidat_candidats = ?
             LIMIT 1',
            [$candidatId]
        );

        if ($numSalarie === false || $numSalarie === null) {
            return null;
        }

        $ids = $this->getActiveProposerIds();
        if (empty($ids) || !in_array((int) $numSalarie, $ids, true)) {
            return null;
        }

        return $this->createQueryBuilder('i')
            ->where('i.numSalarie_Intervenants = :id')
            ->andWhere('i.archive = :archive')
            ->setParameter('id', (int) $numSalarie)
            ->setParameter('archive', 0)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Lookup flexible : essaie toutes les variantes d'identifiant connues,
     * en se limitant aux intervenants ayant un proposer PREST actif.
     *
     * Essais dans l'ordre :
     *   1. exact (numSs ou numSalarie)
     *   2. points → espaces  (1.85.06… → 1 85 06…)
     *   3. espaces → points  (1 85 06… → 1.85.06…)
     *   4. chiffres seuls si l'identifiant semble être un numSS (15 chiffres)
     *   5. PK entier si l'identifiant est purement numérique
     */
    public function findByAnyIdentifier(string $identifier): ?Intervenant
    {
        $ids = $this->getActiveProposerIds();
        if (empty($ids)) {
            return null;
        }

        $candidates = array_unique(array_filter([
            $identifier,
            str_replace('.', ' ', $identifier),
            str_replace(' ', '.', $identifier),
        ]));

        // Ajout de la version sans séparateur uniquement si ça ressemble à un numSS
        $digitsOnly = preg_replace('/\D/', '', $identifier);
        if (strlen($digitsOnly) === 15) {
            $candidates[] = $digitsOnly;
        }

        foreach ($candidates as $candidate) {
            $result = $this->createQueryBuilder('i')
                ->where('i.numSs = :v OR i.numSalarie = :v')
                ->andWhere('i.archive = :archive')
                ->andWhere('i.numSalarie_Intervenants IN (:ids)')
                ->setParameter('v', $candidate)
                ->setParameter('archive', 0)
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getOneOrNullResult();

            if ($result) {
                return $result;
            }
        }

        // Dernier recours : clé primaire entière
        if (ctype_digit($identifier) && in_array((int) $identifier, $ids, true)) {
            return $this->createQueryBuilder('i')
                ->where('i.numSalarie_Intervenants = :id')
                ->andWhere('i.archive = :archive')
                ->setParameter('id', (int) $identifier)
                ->setParameter('archive', 0)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return null;
    }

    public function findByNomOrPrenom(string $search): array
    {
        $ids = $this->getActiveProposerIds();
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('i')
            ->where('(i.nom LIKE :search OR i.prenom LIKE :search)')
            ->andWhere('i.archive = :archive')
            ->andWhere('i.numSalarie_Intervenants IN (:ids)')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('archive', 0)
            ->setParameter('ids', $ids)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countActifs(): int
    {
        $ids = $this->getActiveProposerIds();
        if (empty($ids)) {
            return 0;
        }

        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.numSalarie_Intervenants)')
            ->where('i.numSalarie_Intervenants IN (:ids)')
            ->andWhere('i.archive = :archive')
            ->setParameter('ids', $ids)
            ->setParameter('archive', 0)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les intervenants ayant un planning actif dans proposer.
     * (Identique à countActifs — conservé pour compatibilité sémantique.)
     */
    public function countAvecPlanningActif(): int
    {
        return $this->countActifs();
    }

    public function findDisponiblesPourDate(\DateTimeInterface $date): array
    {
        $ids = $this->getActiveProposerIds();
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('i')
            ->where('i.archive = :archive')
            ->andWhere('i.numSalarie_Intervenants IN (:ids)')
            ->andWhere('i.dateEntree <= :date OR i.dateEntree IS NULL')
            ->andWhere('(i.dateSortie >= :date OR i.dateSortie IS NULL)')
            ->setParameter('archive', 0)
            ->setParameter('ids', $ids)
            ->setParameter('date', $date)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Intervenants non archivés ayant au moins une assignation active dans `proposer`.
     * Seuls ces intervenants sont affichés dans l'application (planning réel).
     *
     * @return Intervenant[]
     */
    public function findWithActivePlanning(): array
    {
        $ids = $this->getActiveProposerIds();
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('i')
            ->where('i.numSalarie_Intervenants IN (:ids)')
            ->andWhere('i.archive = :archive')
            ->setParameter('ids', $ids)
            ->setParameter('archive', 0)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Intervenants assignés à une famille précise via proposer (actif).
     *
     * @return Intervenant[]
     */
    public function findByFamilleActif(string $numeroFamille): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // On affine les IDs actifs globaux en les croisant avec ceux de la famille
        $familleIds = $conn->fetchFirstColumn(
            "SELECT DISTINCT p.numSalarie_Intervenants
             FROM proposer p
             INNER JOIN famille f ON f.numero_Famille = p.numero_Famille
             WHERE p.numero_Famille = ?
               AND p.idADH_TypeADH = 'PREST'
               AND (p.idPresta_Prestations = 'MENA' OR p.idPresta_Prestations = 'ENFA')
               AND (p.dateFin_Proposer IS NULL
                    OR p.dateFin_Proposer = '0000-00-00'
                    OR p.dateFin_Proposer >= CURDATE())
               AND (f.mand_Famille = 0 OR f.mand_Famille IS NULL)
               AND ((f.PGE_Famille IS NOT NULL AND f.PGE_Famille != '')
                    OR (f.PM_Famille IS NOT NULL AND f.PM_Famille != ''))",
            [$numeroFamille]
        );

        // Intersection avec les IDs PREST actifs globaux (double sécurité)
        $activeIds  = $this->getActiveProposerIds();
        $ids        = array_values(
            array_intersect(array_map('intval', $familleIds), $activeIds)
        );

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('i')
            ->where('i.numSalarie_Intervenants IN (:ids)')
            ->andWhere('i.archive = :archive')
            ->setParameter('ids', $ids)
            ->setParameter('archive', 0)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}