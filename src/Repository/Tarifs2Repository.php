<?php

namespace App\Repository;

use App\Entity\Tarifs2;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tarifs2>
 *
 * @method Tarifs2|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tarifs2|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tarifs2[]    findAll()
 * @method Tarifs2[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Tarifs2Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tarifs2::class);
    }

    /**
     * Retourne le tarif actif pour une date donnée
     */
    public function findTarifActif(?\DateTimeInterface $date = null): ?Tarifs2
    {
        if (!$date) {
            $date = new \DateTime();
        }

        $moisAnnee = $date->format('m/Y');

        return $this->createQueryBuilder('t')
            ->where('t.dateDebut <= :moisAnnee')
            ->setParameter('moisAnnee', $moisAnnee)
            ->orderBy('t.dateDebut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne tous les tarifs par ordre chronologique
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le tarif pour une période spécifique
     */
    public function findByPeriode(string $moisAnnee): ?Tarifs2
    {
        return $this->createQueryBuilder('t')
            ->where('t.dateDebut <= :moisAnnee')
            ->setParameter('moisAnnee', $moisAnnee)
            ->orderBy('t.dateDebut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calcule le tarif horaire selon les règles
     */
    public function calculerTarifHoraire(float $heures, string $typePrestation, ?\DateTimeInterface $date = null): float
    {
        $tarif = $this->findTarifActif($date);
        
        if (!$tarif) {
            return 0.0;
        }

        if ($typePrestation === 'GE') {
            return $this->calculerTarifGE($heures, $tarif);
        } elseif ($typePrestation === 'M') {
            return $this->calculerTarifM($heures, $tarif);
        }

        return 0.0;
    }

    /**
     * Calcule le tarif pour garde d'enfants
     */
    private function calculerTarifGE(float $heures, Tarifs2 $tarif): float
    {
        // Parser les règles de tarification depuis alheureGE
        $regles = json_decode($tarif->getAlheureGE(), true);
        
        if (!is_array($regles)) {
            return (float) $tarif->getParIntervention();
        }

        foreach ($regles as $regle) {
            if (count($regle) >= 2) {
                $condition = $regle[0];
                $valeur = $regle[1];
                
                // Évaluer la condition (simplifié)
                if ($this->evaluerCondition($condition, $heures)) {
                    return is_numeric($valeur) ? (float) $valeur : (float) $tarif->getParIntervention();
                }
            }
        }

        return (float) $tarif->getParIntervention();
    }

    /**
     * Calcule le tarif pour ménage
     */
    private function calculerTarifM(float $heures, Tarifs2 $tarif): float
    {
        // Pour le ménage, utiliser alheureM si disponible, sinon tarif par intervention
        $alheureM = $tarif->getAlheureM();
        
        if ($alheureM) {
            $regles = json_decode($alheureM, true);
            
            if (is_array($regles)) {
                foreach ($regles as $regle) {
                    if (count($regle) >= 2) {
                        $condition = $regle[0];
                        $valeur = $regle[1];
                        
                        if ($this->evaluerCondition($condition, $heures)) {
                            return is_numeric($valeur) ? (float) $valeur : (float) $tarif->getParIntervention();
                        }
                    }
                }
            }
        }

        return (float) $tarif->getParIntervention();
    }

    /**
     * Évalue une condition de tarification (simplifié)
     */
    private function evaluerCondition(string $condition, float $heures): bool
    {
        // Simplification : extraire les valeurs numériques et les opérateurs
        if (preg_match('/\$h\s*([<>=]+)\s*([\d.]+)/', $condition, $matches)) {
            $operateur = $matches[1];
            $valeur = (float) $matches[2];
            
            switch ($operateur) {
                case '>=':
                    return $heures >= $valeur;
                case '<':
                    return $heures < $valeur;
                case '>':
                    return $heures > $valeur;
                case '<=':
                    return $heures <= $valeur;
            }
        }
        
        return false;
    }
}
