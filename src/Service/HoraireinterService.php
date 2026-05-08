<?php

namespace App\Service;

use App\Entity\Horaire\Horaireinter;
use App\Repository\HoraireinterRepository;
use App\Repository\RelevemensuelinterRepository;
use Doctrine\ORM\EntityManagerInterface;

class HoraireinterService
{
    public function __construct(
        private HoraireinterRepository $repository,
        private RelevemensuelinterRepository $releveRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Ajoute une nouvelle prestation d'heures
     */
    public function ajouterPrestation(array $donnees): Horaireinter
    {
        $horaire = new Horaireinter();
        
        $horaire->setNumFam($donnees['numFam'] ?? null);
        $horaire->setNomFam($donnees['nomFam'] ?? '');
        $horaire->setNumInter($donnees['numInter'] ?? 0);
        $horaire->setDatePresta(new \DateTime($donnees['datePresta']));
        $horaire->setHeureDebutPresta(new \DateTime($donnees['heureDebutPresta']));
        $horaire->setHeureFinPresta(new \DateTime($donnees['heureFinPresta']));
        $horaire->setTypePresta($donnees['typePresta'] ?? '');
        $horaire->setKmAvecEnfant($donnees['kmAvecEnfant'] ?? null);
        $horaire->setAjouterLe(new \DateTime());
        $horaire->setDesactiver(false);
        $horaire->setValiderFam(false);
        
        // Calculer les heures totales
        $heures = $this->calculerHeures(
            $donnees['heureDebutPresta'],
            $donnees['heureFinPresta']
        );
        $horaire->setHeuresTotal($heures);
        
        $this->entityManager->persist($horaire);
        $this->entityManager->flush();
        
        return $horaire;
    }

    /**
     * Calcule les heures entre deux timestamps
     */
    private function calculerHeures(string $debut, string $fin): float
    {
        $dateDebut = new \DateTime($debut);
        $dateFin = new \DateTime($fin);
        $interval = $dateDebut->diff($dateFin);
        
        // Convertir en heures décimales
        $heures = $interval->h + ($interval->i / 60);
        return round($heures, 2);
    }

    /**
     * Retourne les prestations d'un intervenant pour une période
     */
    public function getPrestationsParIntervenant(int $numInter, ?\DateTimeInterface $dateDebut = null, ?\DateTimeInterface $dateFin = null): array
    {
        $qb = $this->repository->createQueryBuilder('h')
            ->where('h.numInter = :numInter')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numInter', $numInter)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'DESC')
            ->addOrderBy('h.heureDebutPresta', 'DESC');

        if ($dateDebut) {
            $qb->andWhere('h.datePresta >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin) {
            $qb->andWhere('h.datePresta <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les prestations d'une famille
     */
    public function getPrestationsParFamille(string $numFam): array
    {
        return $this->repository->createQueryBuilder('h')
            ->where('h.numFam = :numFam')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numFam', $numFam)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'DESC')
            ->addOrderBy('h.heureDebutPresta', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les prestations non validées par une famille
     */
    public function getPrestationsNonValidees(string $numFam): array
    {
        return $this->repository->createQueryBuilder('h')
            ->where('h.numFam = :numFam')
            ->andWhere('h.validerFam = :validerFam')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numFam', $numFam)
            ->setParameter('validerFam', false)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Valide une prestation par la famille
     */
    public function validerPrestation(int $id, string $numFam): bool
    {
        $horaire = $this->repository->find($id);
        
        if (!$horaire || $horaire->getNumFam() !== $numFam) {
            return false;
        }

        $horaire->setValiderFam(true);
        $horaire->setValiderLe(new \DateTime());
        
        $this->entityManager->flush();
        
        return true;
    }

    /**
     * Ajoute une remarque à une prestation
     */
    public function ajouterRemarque(int $id, string $remarque, string $numFam): bool
    {
        $horaire = $this->repository->find($id);
        
        if (!$horaire || $horaire->getNumFam() !== $numFam) {
            return false;
        }

        $horaire->setRemarque($remarque);
        $horaire->setRemarqueLe(new \DateTime());
        
        $this->entityManager->flush();
        
        return true;
    }

    /**
     * Désactive une prestation
     */
    public function desactiverPrestation(int $id): bool
    {
        $horaire = $this->repository->find($id);
        
        if (!$horaire) {
            return false;
        }

        $horaire->setDesactiver(true);
        $horaire->setModifierLe(new \DateTime());
        
        $this->entityManager->flush();
        
        return true;
    }

    /**
     * Modifie une prestation
     */
    public function modifierPrestation(int $id, array $donnees): bool
    {
        $horaire = $this->repository->find($id);
        
        if (!$horaire) {
            return false;
        }

        if (isset($donnees['datePresta'])) {
            $horaire->setDatePresta(new \DateTime($donnees['datePresta']));
        }
        if (isset($donnees['heureDebutPresta']) && isset($donnees['heureFinPresta'])) {
            $horaire->setHeureDebutPresta(new \DateTime($donnees['heureDebutPresta']));
            $horaire->setHeureFinPresta(new \DateTime($donnees['heureFinPresta']));
            
            // Recalculer les heures
            $heures = $this->calculerHeures(
                $donnees['heureDebutPresta'],
                $donnees['heureFinPresta']
            );
            $horaire->setHeuresTotal($heures);
        }
        if (isset($donnees['kmAvecEnfant'])) {
            $horaire->setKmAvecEnfant($donnees['kmAvecEnfant']);
        }

        $horaire->setModifierLe(new \DateTime());
        
        $this->entityManager->flush();
        
        return true;
    }

    public function getPrestation(int $id): ?object
    {
        return $this->repository->find($id);
    }

    /**
     * Vérifie si un intervenant peut encore saisir des heures pour une date donnée
     */
    public function peutSaisirHeures(\DateTimeInterface $dateSaisie): bool
    {
        $config = $this->getConfiguration();
        $delai = $config['nbrJourSaisie'] ?? 7;
        
        $dateLimite = (new \DateTime())->sub(new \DateInterval("P{$delai}D"));
        
        return $dateSaisie >= $dateLimite;
    }

    public function getNbrJourSaisie(): int
    {
        return (int)($this->getConfiguration()['nbrJourSaisie'] ?? 7);
    }

    /**
     * Récupère la configuration depuis configuration.json
     */
    private function getConfiguration(): array
    {
        $configFile = __DIR__ . '/../../configuration.json';
        
        if (!file_exists($configFile)) {
            return [
                'nbrJourSaisie' => 7,
                'nbrPalierTarifGE' => 4,
                'nbrPalierTarifM' => 0
            ];
        }

        $content = file_get_contents($configFile);
        return json_decode($content, true) ?: [];
    }

    /**
     * Compter les heures du mois
     */
    public function countHeuresMois(string $mois): float
    {
        return $this->repository->countByMonth($mois);
    }
    
    /**
     * Compter les heures par intervenant pour un mois
     */
    public function countHeuresParIntervenant(int $intervenantId, string $mois = null): float
    {
        return $this->repository->countByIntervenant($intervenantId, $mois);
    }
    
    /**
     * Compter les heures par famille pour un mois
     */
    public function countHeuresMoisParFamille(int $familleId, string $mois): float
    {
        return $this->repository->countByFamille($familleId, $mois);
    }

    /**
     * Construit la structure JSON du relevé mensuel pour le front-end.
     * @param object|null $intervenant Entité Intervenant (pour le nom dans la signature)
     */
    public function getReleveData(int $numInter, string $type, int $moisOffset, ?object $intervenant = null): array
    {
        $date = new \DateTime('first day of this month');
        if ($moisOffset !== 0) {
            $date->modify("$moisOffset month");
        }
        $year  = (int)$date->format('Y');
        $month = (int)$date->format('m');

        $startDate = new \DateTime("$year-$month-01");
        $endDate   = (clone $startDate)->modify('last day of this month');
        $moisAnnee = $startDate->format('m/Y');

        $prestations = $this->repository->findByIntervenantPeriodType($numInter, $startDate, $endDate, $type);

        $famillesMap = [];
        foreach ($prestations as $p) {
            $nom = $p->getNomFam();
            if (!isset($famillesMap[$nom])) {
                $famillesMap[$nom] = [
                    'nomFam'      => $nom,
                    'numFam'      => $p->getNumFam() ?? '',
                    'prestations' => [],
                    'totalSecondes' => 0,
                    'totalKm'     => 0.0,
                ];
            }
            $dateKey = $p->getDatePresta()->format('Y-m-d');
            $debut   = $p->getHeureDebutPresta();
            $fin     = $p->getHeureFinPresta();

            $famillesMap[$nom]['prestations'][$dateKey][] = $debut->format('H\hi') . ' - ' . $fin->format('H\hi');

            $debutSec = (int)$debut->format('H') * 3600 + (int)$debut->format('i') * 60;
            $finSec   = (int)$fin->format('H') * 3600   + (int)$fin->format('i') * 60;
            $famillesMap[$nom]['totalSecondes'] += max(0, $finSec - $debutSec);

            if ($p->getKmAvecEnfant()) {
                $famillesMap[$nom]['totalKm'] += (float)$p->getKmAvecEnfant();
            }
        }

        $joursNoms = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $jours = [];
        $cur = clone $startDate;
        while ($cur <= $endDate) {
            $jours[] = [
                'date'       => $cur->format('Y-m-d'),
                'jour'       => $joursNoms[(int)$cur->format('N') - 1],
                'numeroJour' => (int)$cur->format('j'),
                'semaine'    => (int)$cur->format('W'),
            ];
            $cur->modify('+1 day');
        }

        $totalKm = array_sum(array_column(array_values($famillesMap), 'totalKm'));

        $releve = $this->releveRepository->findByMoisAnneeIntervenant($moisAnnee, $numInter, $type);
        $signerData = ['etat' => false, 'date' => '', 'nom' => ''];
        if ($releve && $releve->isSigner()) {
            $signerData = [
                'etat' => true,
                'date' => $releve->getSignerLe()?->format('d/m/Y') ?? '',
                'nom'  => $intervenant ? ($intervenant->getPrenom() . ' ' . $intervenant->getNom()) : '',
            ];
        }

        $moisNoms = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];

        $intervenantData = [
            'nom'               => $intervenant?->getNom() ?? '',
            'prenom'            => $intervenant?->getPrenom() ?? '',
            'Téléhone'          => $intervenant?->getTelPortable() ?? '',
            'adresse'           => $intervenant?->getAdresse() ?? '',
            'ville de résidence'=> $intervenant?->getVille() ?? '',
        ];

        return [
            'type'        => $type,
            'periode'     => ['mois' => ucfirst($moisNoms[$month - 1]), 'anner' => (string)$year, 'fin' => $endDate->format('Y-m-d')],
            'familles'    => array_values($famillesMap),
            'jours'       => $jours,
            'totaux'      => ['kmMois' => $totalKm],
            'signer'      => $signerData,
            'intervenant' => $intervenantData,
        ];
    }

    /**
     * Signe le relevé du mois courant ou précédent uniquement
     */
    public function signerReleve(int $numInter, string $type, string $periodeFin): array
    {
        $date      = new \DateTime($periodeFin);
        $moisAnnee = $date->format('m/Y');

        $now      = new \DateTime();
        $prevDate = (clone $now)->modify('-1 month');

        if (!in_array($moisAnnee, [$now->format('m/Y'), $prevDate->format('m/Y')])) {
            return ['success' => false, 'message' => 'Vous ne pouvez signer que le mois actuel ou le mois précédent'];
        }

        $this->releveRepository->signerReleve($moisAnnee, $numInter, $type);

        return ['success' => true];
    }

    /**
     * Enregistre des heures travaillées hors structure pour un intervenant
     */
    public function ajouterHeuresHorsStructure(int $numInter, int $heure, int $minute, string $periodeFin, string $type): array
    {
        $date      = new \DateTime($periodeFin);
        $moisAnnee = $date->format('m/Y');
        $heureTime = new \DateTime(sprintf('1970-01-01 %02d:%02d:00', $heure, $minute));

        $this->releveRepository->ajouterHeureDehors($moisAnnee, $numInter, $type, $heureTime);

        return ['success' => true];
    }
}