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

    public function findAllNonArchived(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.archive = :archive')
            ->setParameter('archive', 0)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findInfosIntervenant(int $id): ?Intervenant
    {
        return $this->createQueryBuilder('i')
            ->where('i.numSalarie_Intervenants = :id')
            ->andWhere('i.archive = :archive')
            ->setParameter('id', $id)
            ->setParameter('archive', 0)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByNumSs(string $numSs): ?Intervenant
    {
        return $this->createQueryBuilder('i')
            ->where('i.numSs = :numSs')
            ->andWhere('i.archive = :archive')
            ->setParameter('numSs', $numSs)
            ->setParameter('archive', 0)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByNumSalarie(string $numSalarie): ?Intervenant
    {
        return $this->createQueryBuilder('i')
            ->where('i.numSalarie = :numSalarie')
            ->andWhere('i.archive = :archive')
            ->setParameter('numSalarie', $numSalarie)
            ->setParameter('archive', 0)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve l'intervenant lié à un candidat via la FK
     * intervenants.candidats_numcandidat_candidats = $candidatId.
     *
     * On interroge la table intervenants directement (pas la vue)
     * pour obtenir le numSalarie_Intervenants, puis on charge l'entité.
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

        return $this->find((int)$numSalarie);
    }

    /**
     * Lookup flexible : essaie toutes les variantes d'identifiant connues.
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
                ->setParameter('v', $candidate)
                ->setParameter('archive', 0)
                ->getQuery()
                ->getOneOrNullResult();

            if ($result) return $result;
        }

        // Dernier recours : clé primaire entière
        if (ctype_digit($identifier)) {
            return $this->find((int)$identifier);
        }

        return null;
    }

    public function findByNomOrPrenom(string $search): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.nom LIKE :search')
            ->orWhere('i.prenom LIKE :search')
            ->andWhere('i.archive = :archive')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('archive', 0)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countActifs(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.numSalarie_Intervenants)')
            ->where('i.archive = :archive')
            ->setParameter('archive', 0)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findDisponiblesPourDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.archive = :archive')
            ->andWhere('i.dateEntree <= :date OR i.dateEntree IS NULL')
            ->andWhere('(i.dateSortie >= :date OR i.dateSortie IS NULL)')
            ->setParameter('archive', 0)
            ->setParameter('date', $date)
            ->orderBy('i.nom', 'ASC')
            ->addOrderBy('i.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
