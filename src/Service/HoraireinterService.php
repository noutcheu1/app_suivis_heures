<?php

namespace App\Service;

use App\Entity\Horaire\Horaireinter;
use App\Repository\AppConfigRepository;
use App\Repository\FamilleRepository;
use App\Repository\HoraireinterRepository;
use App\Repository\IntervenantRepository;
use App\Repository\ProposerRepository;
use App\Repository\RelevemensuelinterRepository;
use App\Repository\TarifFamilleRepository;
use Doctrine\ORM\EntityManagerInterface;

class HoraireinterService
{
    public function __construct(
        private HoraireinterRepository       $repository,
        private RelevemensuelinterRepository $releveRepository,
        private EntityManagerInterface       $entityManager,
        private ProposerRepository           $proposerRepository,
        private FamilleRepository            $familleRepository,
        private AppConfigRepository          $appConfigRepository,
        private TarifFamilleRepository       $tarifFamilleRepository,
        private IntervenantRepository        $intervenantRepository,
    ) {}

    /**
     * Ajoute une nouvelle prestation d'heures.
     * Lève une exception si le couple (numInter, numFam) n'est pas dans proposer.
     */
    public function ajouterPrestation(array $donnees): Horaireinter
    {
        $numInter = (int)($donnees['numInter'] ?? 0);
        $numFam   = $donnees['numFam'] ?? null;

        if ($numInter <= 0) {
            throw new \LogicException('L\'intervenant doit être identifié pour déclarer des heures.');
        }

        if ($numFam && !$this->proposerRepository->isIntervenantAssignedToFamille($numInter, $numFam)) {
            throw new \LogicException(
                sprintf('L\'intervenant %d n\'est pas assigné à la famille %s dans le planning.', $numInter, $numFam)
            );
        }

        $datePresta  = new \DateTime($donnees['datePresta']);
        $heureDebut  = new \DateTime($donnees['heureDebutPresta']);
        $typePresta  = $donnees['typePresta'] ?? '';

        // Interdire les dates futures
        if ($datePresta > new \DateTime('today')) {
            throw new \LogicException('Impossible de déclarer des heures pour une date future.');
        }

        // Si c'est aujourd'hui, l'heure de fin doit être passée
        if ($datePresta->format('Y-m-d') === (new \DateTime('today'))->format('Y-m-d')) {
            $heureFin = \DateTime::createFromFormat('H:i', $donnees['heureFinPresta']);
            if ($heureFin && $heureFin > new \DateTime()) {
                throw new \LogicException('L\'heure de fin n\'est pas encore passée. Vous pourrez déclarer cette prestation une fois terminée.');
            }
        }

        if ($this->repository->existsDoublon($numInter, $datePresta, $heureDebut, $typePresta)) {
            throw new \LogicException('Une prestation avec cette heure de début existe déjà pour ce jour.');
        }

        if ($numFam && $numFam !== '0' && $this->repository->existsDoublonFamilleDate($numInter, $numFam, $datePresta, $typePresta)) {
            throw new \LogicException('Vous avez déjà une prestation enregistrée pour cette famille ce jour-là.');
        }

        // Durée nulle / max 10h / chevauchement
        $heureFin = new \DateTime($donnees['heureFinPresta']);
        $this->validerCreneau($numInter, $datePresta, $heureDebut, $heureFin);

        $horaire = new Horaireinter();

        $horaire->setNumFam($numFam);
        $horaire->setNomFam($donnees['nomFam'] ?? '');
        $horaire->setNumInter($numInter);
        $horaire->setDatePresta(new \DateTime($donnees['datePresta']));
        $horaire->setHeureDebutPresta(new \DateTime($donnees['heureDebutPresta']));
        $horaire->setHeureFinPresta(new \DateTime($donnees['heureFinPresta']));
        $horaire->setTypePresta($donnees['typePresta'] ?? '');
        $horaire->setKmAvecEnfant($donnees['kmAvecEnfant'] ?? null);
        $horaire->setAjouterLe(new \DateTime());
        $horaire->setDesactiver(false);
        $horaire->setDeclarerLeFam(null);

        // Distance trajet intervenant → famille, calculée UNE fois et figée
        // (familles non occasionnelles uniquement). Null si calcul impossible.
        if ($numFam && $numFam !== '0') {
            $km = $this->calculerKmTrajet($numInter, (string) $numFam);
            $horaire->setKmTrajet($km !== null ? (string) $km : null);
        }
        
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

    public function getMoisDisponibles(int $numInter): array
    {
        // Pour chaque type, on ne remonte que les mois où l'intervenant a un planning PREST
        // pour CE type précis (évite d'afficher un relevé ENFA si le planning est MENA only).
        $rows = [];
        foreach (['MENA', 'ENFA'] as $t) {
            $validFamIds = $this->proposerRepository->findFamilleIdsPrestByIntervenant($numInter, $t);
            if (empty($validFamIds)) {
                continue; // pas de planning PREST pour ce type → aucun mois disponible
            }
            foreach ($this->repository->findMoisDisponibles($numInter, $validFamIds) as $row) {
                if (strtoupper($row['typePresta']) === $t) {
                    $rows[] = $row;
                }
            }
        }

        // Tri décroissant : annee DESC, mois DESC, typePresta
        usort($rows, fn($a, $b) =>
            [$b['annee'], $b['mois'], $b['typePresta']] <=> [$a['annee'], $a['mois'], $a['typePresta']]
        );

        return $rows;
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
     * Retourne les prestations non encore déclarées par la famille
     */
    public function getPrestationsNonDeclarees(string $numFam): array
    {
        return $this->repository->createQueryBuilder('h')
            ->where('h.numFam = :numFam')
            ->andWhere('h.declarerLeFam IS NULL')
            ->andWhere('h.desactiver = :desactiver')
            ->setParameter('numFam', $numFam)
            ->setParameter('desactiver', false)
            ->orderBy('h.datePresta', 'ASC')
            ->addOrderBy('h.heureDebutPresta', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée une entrée Horaireinter avec les heures de la famille (intervenant n'a pas encore saisi).
     */
    public function declarerNouvellePresation(
        string $numFam, string $nomFam, int $numInter,
        string $dateStr, string $typePresta,
        string $heureDebut, string $heureFin
    ): bool {
        $debut = \DateTime::createFromFormat('H:i', $heureDebut);
        $fin   = \DateTime::createFromFormat('H:i', $heureFin);
        $date  = \DateTime::createFromFormat('Y-m-d', $dateStr);

        if (!$debut || !$fin || !$date) {
            return false;
        }

        $horaire = new \App\Entity\Horaire\Horaireinter();
        $horaire->setNumFam($numFam);
        $horaire->setNomFam($nomFam);
        $horaire->setNumInter($numInter);
        $horaire->setDatePresta($date);
        $horaire->setHeureDebutPresta(null);
        $horaire->setHeureFinPresta(null);
        $horaire->setTypePresta($typePresta);
        $horaire->setAjouterLe(new \DateTime());
        $horaire->setDesactiver(false);
        $horaire->setHeureDebutFam($debut);
        $horaire->setHeureFinFam($fin);
        $horaire->setDeclarerLeFam(new \DateTime());

        $this->entityManager->persist($horaire);
        $this->entityManager->flush();
        return true;
    }

    /**
     * Enregistre les heures déclarées par la famille pour une prestation
     */
    public function declarerHeuresFam(int $id, string $numFam, string $heureDebut, string $heureFin): bool
    {
        $horaire = $this->repository->find($id);

        if (!$horaire || $horaire->getNumFam() !== $numFam) {
            return false;
        }

        $horaire->setHeureDebutFam(\DateTime::createFromFormat('H:i', $heureDebut) ?: null);
        $horaire->setHeureFinFam(\DateTime::createFromFormat('H:i', $heureFin) ?: null);
        $horaire->setDeclarerLeFam(new \DateTime());

        $this->entityManager->flush();

        return true;
    }

    /**
     * Une prestation est verrouillée si son mois est strictement antérieur au mois courant.
     * Les admins peuvent toujours modifier ; c'est au contrôleur de passer $adminBypass = true.
     */
    public function isVerrouille(Horaireinter $h): bool
    {
        $presta = $h->getDatePresta();
        if (!$presta) {
            return false;
        }
        $limite = (new \DateTime('today'))->modify('-' . $this->getNbrJourSaisie() . ' days');
        return $presta < $limite;
    }

    /**
     * Désactive une prestation
     */
    public function desactiverPrestation(int $id, bool $adminBypass = false): bool
    {
        $horaire = $this->repository->find($id);

        if (!$horaire) {
            return false;
        }

        if (!$adminBypass && $this->isVerrouille($horaire)) {
            return false;
        }

        $horaire->setDesactiver(true);
        $horaire->setModifierLe(new \DateTime());

        $this->entityManager->flush();

        return true;
    }

    public function restaurerPrestation(int $id): bool
    {
        $horaire = $this->repository->find($id);

        if (!$horaire) {
            return false;
        }

        $horaire->setDesactiver(false);
        $horaire->setModifierLe(new \DateTime());

        $this->entityManager->flush();

        return true;
    }

    /**
     * Modifie une prestation
     */
    public function modifierPrestation(int $id, array $donnees, bool $adminBypass = false): bool
    {
        $horaire = $this->repository->find($id);

        if (!$horaire) {
            return false;
        }

        if (!$adminBypass && $this->isVerrouille($horaire)) {
            return false;
        }

        // Vérification doublon lors de la modification
        if (isset($donnees['datePresta']) && isset($donnees['heureDebutPresta'])) {
            $datePresta = new \DateTime($donnees['datePresta']);
            $heureDebut = new \DateTime($donnees['heureDebutPresta']);
            $typePresta = $donnees['typePresta'] ?? $horaire->getTypePresta();
            $numInter   = (int)$horaire->getNumInter();
            $numFam     = $horaire->getNumFam();

            if ($this->repository->existsDoublon($numInter, $datePresta, $heureDebut, $typePresta, $id)) {
                throw new \LogicException('Une prestation avec cette heure de début existe déjà pour ce jour.');
            }

            if ($numFam && $numFam !== '0' && $this->repository->existsDoublonFamilleDate($numInter, $numFam, $datePresta, $typePresta, $id)) {
                throw new \LogicException('Vous avez déjà une prestation enregistrée pour cette famille ce jour-là.');
            }

            // Durée nulle / max 10h / chevauchement — uniquement si les heures changent
            // réellement (modifier le km ou la famille ne doit pas relancer ces contrôles).
            if (isset($donnees['heureFinPresta'])) {
                $heureFin   = new \DateTime($donnees['heureFinPresta']);
                $ancienDeb  = $horaire->getHeureDebutPresta()?->format('H:i');
                $ancienFin  = $horaire->getHeureFinPresta()?->format('H:i');
                $heuresOntChange = $ancienDeb !== $heureDebut->format('H:i')
                    || $ancienFin !== $heureFin->format('H:i');

                if ($heuresOntChange) {
                    $this->validerCreneau($numInter, $datePresta, $heureDebut, $heureFin, $id);
                }
            }
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
        $delai = $this->appConfigRepository->getConfig()->getNbrJourSaisie();
        $dateLimite = (new \DateTime())->sub(new \DateInterval("P{$delai}D"));
        return $dateSaisie >= $dateLimite;
    }

    public function getNbrJourSaisie(): int
    {
        return $this->appConfigRepository->getConfig()->getNbrJourSaisie();
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
    public function countHeuresMoisParFamille(string $familleId, string $mois): float
    {
        return $this->repository->countByFamille($familleId, $mois);
    }



    /**
     * Valide un créneau : durée non nulle, max 10h, pas de chevauchement
     * avec une autre prestation du même intervenant le même jour.
     *
     * @throws \LogicException si une règle n'est pas respectée
     */
    private function validerCreneau(
        int $numInter,
        \DateTimeInterface $datePresta,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        ?int $excludeId = null
    ): void {
        $sec = $heureFin->getTimestamp() - $heureDebut->getTimestamp();
        if ($sec < 0) {
            $sec += 24 * 3600; // passage minuit
        }

        if ($sec === 0) {
            throw new \LogicException('La durée de la prestation ne peut pas être nulle (heure de début = heure de fin).');
        }

        if ($sec > 10 * 3600) {
            throw new \LogicException('Une prestation ne peut pas dépasser 10 heures dans une journée.');
        }

        if ($this->repository->existsChevauchement($numInter, $datePresta, $heureDebut, $heureFin, $excludeId)) {
            throw new \LogicException('Ce créneau chevauche une autre prestation déjà enregistrée ce jour-là.');
        }
    }

    /**
     * Calcule les heures entre deux timestamps
     */
    private function calculerHeures(string $debut, string $fin): float
    {
        $dateDebut = new \DateTime($debut);
        $dateFin = new \DateTime($fin);

        $seconds = $dateFin->getTimestamp() - $dateDebut->getTimestamp();

            if ($seconds < 0) {
                $seconds += 24 * 3600;
            }

            return round($seconds / 3600, 2);
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

        if ($type == 'MENA') {
            // Période du 25 du mois précédent au 24 du mois courant
            $startDate = new \DateTime(sprintf('%04d-%02d-25', $year, $month));
            $startDate->modify('-1 month');
            $endDate = new \DateTime(sprintf('%04d-%02d-24', $year, $month));
        } else {
            // Type 'enfant' : période du 1er au dernier jour du mois courant
            $startDate = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
            $endDate   = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
            $endDate->modify('last day of this month');
        }

        $moisAnnee = sprintf('%02d/%04d', $month, $year);

        $prestations = $this->repository->findByIntervenantPeriodType($numInter, $startDate, $endDate, $type);

        // N'inclure que les heures pour des familles ayant un planning PREST pour CE type
        // (actif ou historique). Source de vérité : table proposer (idADH_TypeADH = 'PREST').
        // Les familles occasionnelles (numFam null ou '0') sont toujours incluses.
        $validFamIds = $this->proposerRepository->findFamilleIdsPrestByIntervenant($numInter, $type);
        $prestations = array_values(array_filter($prestations, function ($p) use ($validFamIds) {
            $numFam = $p->getNumFam();
            return $numFam === null || $numFam === '' || $numFam === '0'
                || in_array($numFam, $validFamIds, true);
        }));

        // ── Distances trajet : lues depuis kmTrajet figé sur chaque prestation ──────
        // (calculé et stocké à la déclaration). Plus AUCUN appel API ici → relevé
        // instantané et fiable. Les prestations d'une même famille partagent la
        // même distance ; on prend la valeur figée (max non nulle par sécurité).
        $distancesParFamille = [];
        $exoneresKm          = [];
        foreach ($prestations as $p) {
            $numFam = $p->getNumFam() ?? '';
            if (!$numFam) {
                continue;
            }
            $km = $p->getKmTrajet();
            if ($km !== null) {
                $distancesParFamille[$numFam] = max($distancesParFamille[$numFam] ?? 0.0, (float) $km);
            } elseif (!isset($distancesParFamille[$numFam])) {
                $distancesParFamille[$numFam] = 0.0;
            }
            if (!isset($exoneresKm[$numFam]) && $this->tarifFamilleRepository->isExonereKm($numFam, $type)) {
                $exoneresKm[$numFam] = true;
            }
        }

        // ── Construction de la map familles ──────────────────────────────────
        $famillesMap = [];
        foreach ($prestations as $p) {
            $nom = $p->getNomFam();
            if (!isset($famillesMap[$nom])) {
                $numFam  = $p->getNumFam() ?? '';
                $famille = $numFam ? $this->familleRepository->findByNumero($numFam) : null;

                // Distance facturable : règle métier
                //   - hors Rennes              → distance réelle
                //   - Rennes + exonéré (payé)  → distance réelle
                //   - Rennes + NON exonéré     → 0
                $distReelle = $distancesParFamille[$numFam] ?? 0.0;
                $estExonere = isset($exoneresKm[$numFam]);
                $estRennes  = $famille && $this->estARennes($famille->getVille(), $famille->getCodePostal());
                $distanceAller = ($estRennes && !$estExonere) ? 0.0 : $distReelle;

                $famillesMap[$nom] = [
                    'nomFam'         => $nom,
                    'numFam'         => $numFam,
                    'ville_Famille'  => $famille?->getVille() ?? '',
                    'distanceAller'  => $distanceAller,
                    'exonereKm'      => $estExonere,
                    'prestations'    => [],
                    'totalSecondes'  => 0,
                    'totalKm'        => 0.0,
                    'totalKmEnfants' => 0.0,
                    'nbPrestations'  => 0,
                ];
            }
            $dateKey  = $p->getDatePresta()->format('Y-m-d');
            $debut    = $p->getHeureDebutPresta();
            $fin      = $p->getHeureFinPresta();
            $debutSec = $debut->getTimestamp() - $debut->setTime(0,0)->getTimestamp();
            $finSec   = $fin->getTimestamp()  - $fin->setTime(0,0)->getTimestamp();
            if ($finSec < $debutSec) $finSec += 86400;
            $dureeSec = $finSec - $debutSec;

            // Centièmes : 2h30 → "2,50"
            $timeStr = number_format($dureeSec / 3600, 2, ',', '');
            $famillesMap[$nom]['prestations'][$dateKey][] = $timeStr;

            $famillesMap[$nom]['totalSecondes']  += $dureeSec;
            $famillesMap[$nom]['nbPrestations']++;
            // Km facturable, plafonné à 15/trajet. distanceAller encode déjà la règle :
            // Rennes non exonéré = 0 ; hors Rennes et Rennes-exonéré = distance réelle.
            $famillesMap[$nom]['totalKm'] += $famillesMap[$nom]['distanceAller'] > 15
                ? 15.0
                : $famillesMap[$nom]['distanceAller'];
            
            $famillesMap[$nom]['totalKmEnfants'] += $type === 'ENFA'
                ? (float)($p->getKmAvecEnfant() ?? 0)
                : 0.0;
        }

        $joursNoms = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $calStart = clone $startDate;
        $calEnd   = clone $endDate;
        $jours = [];
        $cur = clone $calStart;
        while ($cur <= $calEnd) {
            $jours[] = [
                'date'       => $cur->format('Y-m-d'),
                'jour'       => $joursNoms[(int)$cur->format('N') - 1],
                'numeroJour' => (int)$cur->format('j'),
                'semaine'    => (int)$cur->format('W'),
            ];
            $cur->modify('+1 day');
        }

        // totalKm = km facturables (familles non exonérées)
        // totalKmAffichage = tous les km pour affichage dans le relevé (y compris exonérées)
        $totalKm        = array_sum(array_column(array_values($famillesMap), 'totalKm'));
        $totalKmEnfants = array_sum(array_column(array_values($famillesMap), 'totalKmEnfants'));

        // Pour l'affichage : si toutes les familles sont exonérées, on utilise quand même leur distance × nb prestations
        $totalKmAffichage = $totalKm;
        if ($totalKmAffichage == 0) {
            foreach ($famillesMap as $fam) {
                if ($fam['exonereKm']) {
                    $totalKmAffichage += $fam['distanceAller'] * $fam['nbPrestations'];
                }
            }
        }

        $releve = $this->releveRepository->findByMoisAnneeIntervenant($moisAnnee, $numInter, $type);
        $signerData = ['etat' => false, 'date' => '', 'nom' => ''];
        if ($releve && $releve->isSigner()) {
            $signerData = [
                'etat' => true,
                'date' => $releve->getSignerLe()?->format('d/m/Y') ?? '',
                'nom'  => $intervenant ? ($intervenant->getPrenom() . ' ' . $intervenant->getNom()) : '',
            ];
        }

        $heureDehors = null;
        if ($releve?->getHeureDehors()) {
            $h = (int)$releve->getHeureDehors()->format('H');
            $m = (int)$releve->getHeureDehors()->format('i');
            $heureDehors = sprintf('%dh%02d', $h, $m);
        }

        $moisNoms = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];

        $intervenantData = [
            'nom'               => $intervenant?->getNom() ?? '',
            'prenom'            => $intervenant?->getPrenom() ?? '',
            'numSalarie'        => $intervenant?->getNumSalarie() ?? '',
            'Téléhone'          => $intervenant?->getTelPortable() ?? '',
            'adresse'           => $intervenant?->getAdresse() ?? '',
            'ville de résidence'=> $intervenant?->getVille() ?? '',
        ];

        $familles = array_values($famillesMap);
        usort($familles, fn($a, $b) => strcmp($a['nomFam'], $b['nomFam']));

        return [
            'type'        => $type,
            'periode'     => ['mois' => ucfirst($moisNoms[$month - 1]), 'anner' => (string)$year, 'fin' => $endDate->format('Y-m-d')],
            'familles'    => $familles,
            'jours'       => $jours,
            'totaux'      => ['kmMois' => $totalKm, 'kmEnfantsMois' => $totalKmEnfants],
            'signer'      => $signerData,
            'heureDehors' => $heureDehors,
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
     * Statistiques du mois courant pour le dashboard intervenant
     */
    public function getDashboardStats(int $numInter): array
    {
        $start = new \DateTime('first day of this month 00:00:00');
        $end   = new \DateTime('last day of this month 23:59:59');
        $prestations = $this->getPrestationsParIntervenant($numInter, $start, $end);
        $cmptvalider = 0;
        $cmpt = 0;
        $totalSec = 0; $declareesSec = 0; $attenteSec = 0; $nbEcarts = 0; $totalKm = 0.0;
        foreach ($prestations as $p) {
            $d = (int)$p->getHeureDebutPresta()->format('H') * 3600 + (int)$p->getHeureDebutPresta()->format('i') * 60;
            $f = (int)$p->getHeureFinPresta()->format('H')   * 3600 + (int)$p->getHeureFinPresta()->format('i')   * 60;
            $dur = max(0, $f - $d);
            $totalSec += $dur;
            $cmpt++;
            $totalKm += (float)($p->getKmAvecEnfant() ?? 0);
            if ($p->getDeclarerLeFam()) {
                $cmptvalider++;
                $declareesSec += $dur;
                $df = $p->getHeureDebutFam(); $ff = $p->getHeureFinFam();
                if ($df && $ff) {
                    $dfs = (int)$df->format('H') * 3600 + (int)$df->format('i') * 60;
                    $ffs = (int)$ff->format('H') * 3600 + (int)$ff->format('i') * 60;
                    if (abs(($ffs - $dfs) - $dur) > 60) { $nbEcarts++; }
                }
            } else {
                $attenteSec += $dur;
            }
        }

        return [
            'totalHeures'    => $this->secToHhMm($totalSec),
            'heuresValidees' => $this->secToHhMm($declareesSec),
            'heuresAttente'  => $this->secToHhMm($attenteSec),
            'nbSignalements' => $nbEcarts,
            'cmptvalider'    => $cmptvalider,
            'cmpt'           => $cmpt,
            'nbPrestations'  => count($prestations),
            'totalKm'        => $totalKm,
        ];
    }

    /**
     * Relevés du mois courant et précédent non encore signés
     */
    public function getRelevesASigner(int $numInter): array
    {
        $now  = new \DateTime();
        $prev = (clone $now)->modify('-1 month');
        $nonSignes = [];

        foreach ([$now, $prev] as $date) {
            $mois  = $date->format('m/Y');
            $m     = (int)$date->format('m');
            $y     = (int)$date->format('Y');
            $start = new \DateTime(sprintf('%04d-%02d-25', $y, $m));
            $start->modify('-1 month');
            $end   = new \DateTime(sprintf('%04d-%02d-24', $y, $m));

            foreach (['ENFA', 'MENA'] as $type) {
                // Vérifie que l'intervenant a un planning PREST pour CE type
                $validIds = $this->proposerRepository->findFamilleIdsPrestByIntervenant($numInter, $type);
                if (empty($validIds)) {
                    continue; // pas de service de ce type → pas de relevé à signer
                }
                $allPrestations = $this->repository->findByIntervenantPeriodType($numInter, $start, $end, $type);
                $nb = count(array_filter($allPrestations, function ($p) use ($validIds) {
                    $n = $p->getNumFam();
                    return $n === null || $n === '' || $n === '0' || in_array($n, $validIds, true);
                }));
                if ($nb === 0) continue;
                $releve = $this->releveRepository->findByMoisAnneeIntervenant($mois, $numInter, $type);
                if (!$releve || !$releve->isSigner()) {
                    $nonSignes[] = [
                        'mois'         => $mois,
                        'type'         => $type,
                        'libelle'      => $type === 'ENFA' ? "Garde d'enfants" : 'Ménage',
                        'nbPrestations'=> $nb,
                    ];
                }
            }
        }
        return $nonSignes;
    }

    /**
     * Statistiques du mois courant pour le dashboard famille
     */
    public function getDashboardStatsFamille(string $numFam): array
    {
        $start = new \DateTime('first day of this month 00:00:00');
        $end   = new \DateTime('last day of this month 23:59:59');

        $all = $this->repository->createQueryBuilder('h')
            ->where('h.numFam = :numFam')
            ->andWhere('h.desactiver = :d')
            ->andWhere('h.datePresta BETWEEN :s AND :e')
            ->setParameter('numFam', $numFam)
            ->setParameter('d', false)
            ->setParameter('s', $start)
            ->setParameter('e', $end)
            ->getQuery()->getResult();

        $totalSec = 0; $declareesSec = 0; $attenteSec = 0; $nbEcarts = 0;
        foreach ($all as $p) {
            // Utiliser heures famille si heures intervenant absentes
            $debut = $p->getHeureDebutPresta() ?? $p->getHeureDebutFam();
            $fin   = $p->getHeureFinPresta()   ?? $p->getHeureFinFam();
            if (!$debut || !$fin) { continue; }
            $dur = $this->durationSec($debut, $fin);
            $totalSec += $dur;
            if ($p->getDeclarerLeFam()) {
                $declareesSec += $dur;
                $df = $p->getHeureDebutFam(); $ff = $p->getHeureFinFam();
                if ($df && $ff && $p->getHeureDebutPresta() && $p->getHeureFinPresta()) {
                    if (abs($this->durationSec($df, $ff) - $dur) > 60) { $nbEcarts++; }
                }
            } else {
                $attenteSec += $dur;
            }
        }

        return [
            'totalHeures'    => $this->secToHhMm($totalSec),
            'heuresValidees' => $this->secToHhMm($declareesSec),
            'heuresAttente'  => $this->secToHhMm($attenteSec),
            'nbSignalements' => $nbEcarts,
            'nbPrestations'  => count($all),
            'nbAttente'      => count(array_filter($all, fn($p) => !$p->getDeclarerLeFam())),
        ];
    }

    /**
     * Prestations déclarées par la famille avec un écart vs heures intervenant
     */
    public function getSignalementsFamille(string $numFam): array
    {
        $prestations = $this->repository->createQueryBuilder('h')
            ->where('h.numFam = :numFam')
            ->andWhere('h.desactiver = :d')
            ->andWhere('h.declarerLeFam IS NOT NULL')
            ->andWhere('h.heureDebutFam IS NOT NULL')
            ->andWhere('h.heureFinFam IS NOT NULL')
            ->setParameter('numFam', $numFam)
            ->setParameter('d', false)
            ->orderBy('h.datePresta', 'DESC')
            ->getQuery()->getResult();

        return array_filter($prestations, function ($p) {
            if (!$p->getHeureDebutPresta() || !$p->getHeureFinPresta()) { return false; }
            $durInter = $this->durationSec($p->getHeureDebutPresta(), $p->getHeureFinPresta());
            $durFam   = $this->durationSec($p->getHeureDebutFam(), $p->getHeureFinFam());
            return abs($durFam - $durInter) > 60;
        });
    }

    /**
     * Toutes les prestations de toutes les familles avec un écart entre heures fam et heures inter.
     * Utilisé par l'admin pour choisir la source de facturation.
     */
    public function getTousLesEcarts(): array
    {
        $prestations = $this->repository->createQueryBuilder('h')
            ->andWhere('h.desactiver = :d')
            ->andWhere('h.declarerLeFam IS NOT NULL')
            ->andWhere('h.heureDebutFam IS NOT NULL')
            ->andWhere('h.heureFinFam IS NOT NULL')
            ->setParameter('d', false)
            ->orderBy('h.datePresta', 'DESC')
            ->getQuery()->getResult();

        return array_values(array_filter($prestations, function ($p) {
            if (!$p->getHeureDebutPresta() || !$p->getHeureFinPresta()) { return false; }
            $durInter = $this->durationSec($p->getHeureDebutPresta(), $p->getHeureFinPresta());
            $durFam   = $this->durationSec($p->getHeureDebutFam(), $p->getHeureFinFam());
            return abs($durFam - $durInter) > 60;
        }));
    }

    /**
     * Définit la source de facturation ('inter' ou 'fam') pour une prestation.
     */
    public function choisirSourceFacturation(int $id, string $source): bool
    {
        if (!in_array($source, ['inter', 'fam'], true)) {
            return false;
        }
        $horaire = $this->repository->find($id);
        if (!$horaire) {
            return false;
        }
        $horaire->setSourceFacturation($source);
        $this->entityManager->flush();
        return true;
    }

    /**
     * Agrège les prestations par (numInter, typePresta) pour la préparation de paie.
     * ENFA : mois complet. MENA : du 25 du mois précédent au 24 du mois courant.
     */
    public function getPreparationPaie(int $year, int $month): array
    {
        // Période universelle : 25 du mois précédent au 24 du mois courant
        $periodEnd   = new \DateTime(sprintf('%04d-%02d-24', $year, $month));
        $periodStart = new \DateTime(sprintf('%04d-%02d-25', $year, $month));
        $periodStart->modify('-1 month');

        $grouped = [];
        foreach ([
            ['ENFA', $periodStart, $periodEnd],
            ['MENA', $periodStart, $periodEnd],
        ] as [$type, $start, $end]) {
            foreach ($this->repository->findByPeriodAndType($type, $start, $end) as $h) {
                $key = $h->getNumInter() . '|' . $h->getTypePresta();
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'numInter'    => $h->getNumInter(),
                        'typePresta'  => $h->getTypePresta(),
                        'totalSec'    => 0,
                        'nbrTrajet'   => 0,
                        'kmAvecEnfant'=> 0.0,
                        'familles'    => [],
                    ];
                }
                if ($h->getHeureDebutPresta() && $h->getHeureFinPresta()) {
                    $sec = $h->getHeureFinPresta()->getTimestamp() - $h->getHeureDebutPresta()->getTimestamp();
                    if ($sec < 0) $sec += 86400;
                    $grouped[$key]['totalSec'] += $sec;
                }
                $grouped[$key]['nbrTrajet']++;
                $grouped[$key]['kmAvecEnfant'] += (float)($h->getKmAvecEnfant() ?? 0);
                if ($h->getNumFam()) {
                    $grouped[$key]['familles'][$h->getNumFam()] = true;
                }
            }
        }

        foreach ($grouped as &$g) {
            $s = $g['totalSec'];
            $g['heuresDecimal']   = str_replace('.', ',', number_format($s / 3600, 2));
            $g['heuresFormatted'] = sprintf('%dh%02d', intdiv($s, 3600), ($s % 3600) / 60);
            $g['familles']        = array_keys($g['familles']);
        }
        unset($g);

        usort($grouped, fn($a, $b) => $a['typePresta'] <=> $b['typePresta'] ?: $a['numInter'] <=> $b['numInter']);

        return $grouped;
    }

    private function durationSec(?\DateTimeInterface $debut, ?\DateTimeInterface $fin): int
    {
        if (!$debut || !$fin) { return 0; }
        return max(0, ((int)$fin->format('H') * 3600 + (int)$fin->format('i') * 60)
                    - ((int)$debut->format('H') * 3600 + (int)$debut->format('i') * 60));
    }

    private function secToHhMm(int $sec): string
    {
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        return sprintf('%dh%02d', $h, $m);
    }

    /**
     * Retourne true si la famille est à Rennes (km trajet = 0).
     * On se base sur le nom de ville exact ET les codes postaux officiels de Rennes.
     * Bruz (35170), Cesson (35510), etc. → false → km calculé.
     */
    /**
     * Calcule la distance routière (km) entre l'adresse d'un intervenant et une famille.
     * Retourne 0.0 si la famille est à Rennes, et null si le calcul échoue
     * (adresse manquante, géocodage ou routage indisponible).
     */
    public function calculerKmTrajet(int $numInter, string $numFam): ?float
    {
        $intervenant = $this->intervenantRepository->find($numInter);
        $famille     = $this->familleRepository->findByNumero($numFam);

        if (!$intervenant || !$intervenant->getAdresse() || !$famille || !$famille->getAdresse()) {
            return null;
        }

        // On stocke TOUJOURS la distance réelle (même à Rennes). La règle
        // "Rennes non exonéré → 0" est appliquée à l'affichage du relevé,
        // car une famille à Rennes peut être exonérée (= km payé quand même).

        $adresseInter = trim(
            ($intervenant->getAdresse() ?? '') . ', ' .
            ($intervenant->getCodePostal() ?? '') . ' ' .
            ($intervenant->getVille() ?? '')
        );
        $adresseFam = trim(
            $famille->getAdresse() . ', ' .
            ($famille->getCodePostal() ?? '') . ' ' .
            ($famille->getVille() ?? '')
        );

        $geo         = $this->geocodeAddressesBatch([$adresseInter, $adresseFam]);
        $coordsInter = $geo[$adresseInter] ?? null;
        $coordsFam   = $geo[$adresseFam]   ?? null;
        if (!$coordsInter || !$coordsFam) {
            return null; // géocodage échoué → on ne fige pas une fausse valeur
        }

        $distances = $this->getDistancesBatch(['x' => ['start' => $coordsInter, 'end' => $coordsFam]]);
        return $distances['x'] ?? null;
    }

    private function estARennes(?string $ville, ?string $cp): bool
    {
        $cpRennes   = ['35000', '35200', '35700'];
        $nomRennes  = 'rennes';

        $cpMatch    = $cp   && in_array(trim($cp), $cpRennes, true);
        $villeMatch = $ville && strtolower(trim($ville)) === $nomRennes;

        return $cpMatch || $villeMatch;
    }

    /**
     * Géocode plusieurs adresses en parallèle via Nominatim.
     * Retourne un tableau indexé par adresse => [lat, lon] ou null.
     *
     * @param  string[] $addresses
     * @return array<string, array{0:float,1:float}|null>
     */
    private function geocodeAddressesBatch(array $addresses): array
    {
        $addresses = array_unique(array_filter($addresses));
        if (!$addresses) return [];

        $mh      = curl_multi_init();
        $handles = [];

        foreach ($addresses as $addr) {
            $url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($addr) . '&format=json&limit=1';
            $ch  = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_HTTPHEADER     => ['Accept-Language: fr', 'User-Agent: ChaudoudouxApp/1.0'],
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$addr] = $ch;
        }

        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 1.0);
        } while ($running > 0 && $status === CURLM_OK);

        $results = [];
        foreach ($handles as $addr => $ch) {
            $errno = curl_errno($ch);
            $body  = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            if ($errno !== CURLE_OK || !$body) {
                $results[$addr] = null;
                continue;
            }
            $data = json_decode($body, true);
            $results[$addr] = (json_last_error() === JSON_ERROR_NONE && !empty($data[0]))
                ? [(float)$data[0]['lat'], (float)$data[0]['lon']]
                : null;
        }
        curl_multi_close($mh);

        return $results;
    }

    /**
     * Calcule les distances routières en parallèle via OSRM pour plusieurs paires.
     * Chaque paire est [start:[lat,lon], end:[lat,lon]].
     * Retourne un tableau indexé par clé => distance en km ou null.
     *
     * @param  array<string, array{start:array, end:array}> $pairs
     * @return array<string, float|null>
     */
    private function getDistancesBatch(array $pairs): array
    {
        if (!$pairs) return [];

        $mh      = curl_multi_init();
        $handles = [];

        foreach ($pairs as $key => $pair) {
            [$sLat, $sLon] = $pair['start'];
            [$eLat, $eLon] = $pair['end'];
            $url = sprintf(
                'https://router.project-osrm.org/route/v1/driving/%f,%f;%f,%f?overview=false',
                $sLon, $sLat, $eLon, $eLat
            );
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT        => 8,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 1.0);
        } while ($running > 0 && $status === CURLM_OK);

        $results = [];
        foreach ($handles as $key => $ch) {
            $errno = curl_errno($ch);
            $body  = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            if ($errno !== CURLE_OK || !$body) {
                $results[$key] = null;
                continue;
            }
            $data  = json_decode($body, true);
            $distM = (json_last_error() === JSON_ERROR_NONE)
                ? ($data['routes'][0]['legs'][0]['distance'] ?? null)
                : null;
            $results[$key] = $distM !== null ? round($distM / 1000, 1) : null;
        }
        curl_multi_close($mh);

        return $results;
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