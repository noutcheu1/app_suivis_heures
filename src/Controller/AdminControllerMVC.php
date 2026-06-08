<?php

namespace App\Controller;

use App\Entity\Horaire\Horaireinter;
use App\Entity\Horaire\VacancesConfig;
use App\Entity\Principal\Proposer;
use App\Repository\AppConfigRepository;
use App\Repository\FamilleRepository;
use App\Repository\TarifFamilleRepository;
use App\Repository\HoraireinterRepository;
use App\Repository\IntervenantRepository;
use App\Repository\ParentFamilleRepository;
use App\Repository\ProposerRepository;
use App\Repository\RelevemensuelinterRepository;
use App\Repository\VacancesConfigRepository;
use App\Service\AuthService;
use App\Service\FactureService;
use App\Service\FamilleService;
use App\Service\HoraireinterService;
use App\Service\IntervenantService;
use App\Service\ReleveMensuelFamilleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour les fonctionnalités administratives selon le pattern MVC
 */
final class AdminControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService                 $authService,
        private IntervenantService          $intervenantService,
        private FamilleService              $familleService,
        private HoraireinterService         $horaireService,
        private ReleveMensuelFamilleService $releveMensuelFamilleService,
        private FactureService              $factureService,
        private VacancesConfigRepository    $vacancesRepo,
        private AppConfigRepository         $appConfigRepo,
        private HoraireinterRepository      $horaireRepo,
        private ProposerRepository          $proposerRepo,
        private FamilleRepository           $familleRepo,
        private ParentFamilleRepository     $parentRepo,
        private IntervenantRepository       $intervenantRepo,
        private TarifFamilleRepository          $tarifFamilleRepo,
        private RelevemensuelinterRepository    $relevemensuelinterRepo,
        private EntityManagerInterface          $em,
    ) {}

    #[Route('/admin-mvc/dashboard', name: 'admin_dashboard_mvc')]
    public function dashboard(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $stats = [
            'total_intervenants' => $this->intervenantService->countIntervenants(),
            'total_familles' => $this->familleService->countFamilles(),
            'heures_ce_mois' => $this->horaireService->countHeuresMois(date('m/Y')),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'auth' => $this->authService->check(),
            'stats' => $stats,
        ]);
    }

    #[Route('/admin-mvc/intervenants', name: 'admin_intervenants_mvc')]
    public function listeIntervenants(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $intervenants = $this->intervenantService->getTousLesIntervenants();

        return $this->render('admin/intervenants/list.html.twig', [
            'auth' => $this->authService->check(),
            'users' => $intervenants,
        ]);
    }

    #[Route('/admin-mvc/familles', name: 'admin_familles_mvc')]
    public function listeFamilles(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $familles = $this->familleService->getToutesLesFamilles();

        $tarifsActifs = $this->tarifFamilleRepo->findTousActifs();
        $kmExonerations = [];
        foreach ($tarifsActifs as $t) {
            $kmExonerations[$t->getNumFam()][$t->getTypePresta()] = $t->isExonereKm();
        }

        return $this->render('admin/familles/list.html.twig', [
            'auth'           => $this->authService->check(),
            'users'          => $familles,
            'kmExonerations' => $kmExonerations,
        ]);
    }


      #[Route('/admin-mvc/familles/{type}', name: 'liste_Familles_type_mvc')]
    public function listeFamillesBY(string $type, Request $request): Response
    {

        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $familles = $this->familleService->getFamillebytype($type);

        $tarifsActifs = $this->tarifFamilleRepo->findTousActifs();
        $kmExonerations = [];
        foreach ($tarifsActifs as $t) {
            $kmExonerations[$t->getNumFam()][$t->getTypePresta()] = $t->isExonereKm();
        }

        $kmType = match(strtolower($type)) {
            'prestgardeenfants' => 'GE',
            'prestmenage'       => 'M',
            default             => null,
        };

        return $this->render('admin/familles/list_type.html.twig', [
            'auth'           => $this->authService->check(),
            'users'          => $familles,
            'kmExonerations' => $kmExonerations,
            'kmType'         => $kmType,
        ]);
    }


    #[Route('/admin-mvc/intervenant/{id}', name: 'admin_intervenant_detail_mvc')]
    public function detailIntervenant(int $id, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $intervenant = $this->intervenantService->getIntervenantParId($id);
        
        if (!$intervenant) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        $prestations = $this->horaireService->getPrestationsParIntervenant($id);

        return $this->render('admin/intervenant_detail.html.twig', [
            'auth' => $this->authService->check(),
            'intervenant' => $intervenant,
            'prestations' => $prestations,
        ]);
    }

    #[Route('/admin-mvc/famille/{numFam}', name: 'admin_famille_detail_mvc')]
    public function detailFamille(string $numFam, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $intervenants = $this->intervenantRepo->findAllNonArchived();
        $prestations  = $this->horaireService->getPrestationsParFamille($numFam);
        $parents      = $this->parentRepo->findByFamille($numFam);
        $typeAdhs     = $this->proposerRepo->findAllTypeAdh();
        $plannings    = $this->proposerRepo->findActivesByFamille($numFam);

        $interMap = [];
        foreach ($intervenants as $i) {
            $interMap[$i->getId()] = trim($i->getNom() . ' ' . $i->getPrenom());
        }

        return $this->render('admin/famille_detail.html.twig', [
            'auth'         => $this->authService->check(),
            'famille'      => $famille,
            'prestations'  => $prestations,
            'parents'      => $parents,
            'intervenants' => $intervenants,
            'interMap'     => $interMap,
            'typeAdhs'     => $typeAdhs,
            'plannings'    => $plannings,
        ]);
    }

    #[Route('/admin-mvc/famille/{numFam}/ajouter-planning', name: 'admin_ajouter_planning_mvc', methods: ['POST'])]
    public function ajouterPlanning(string $numFam, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        if (!$this->isCsrfTokenValid('ajouter_planning', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_famille_detail_mvc', ['numFam' => $numFam]);
        }

        $numInter       = (int) $request->request->get('numInter');
        $typePrestation = $request->request->get('typePrestation', 'ENFA');
        $jour           = $request->request->get('jour');
        $heureDebut     = $request->request->get('heureDebut');
        $heureFin       = $request->request->get('heureFin') ?: null;
        $dateDeb        = $request->request->get('dateDeb');
        $dateFin        = $request->request->get('dateFin') ?: null;
        $frequence      = (int) $request->request->get('frequence', 1);
        $typeAdh        = $request->request->get('typeAdh', 'CESU');

        try {
            $proposer = new Proposer();
            $proposer->setTypePrestation($typePrestation);
            $proposer->setNumSalarie($numInter);
            $proposer->setNumeroFamille($numFam);
            $proposer->setTypeAdh($typeAdh);
            $proposer->setJour($jour);
            $proposer->setHeureDebut(new \DateTime($heureDebut));
            $proposer->setHeureFin($heureFin ? new \DateTime($heureFin) : null);
            $proposer->setDateDeb(new \DateTime($dateDeb));
            $proposer->setDateFin($dateFin ? new \DateTime($dateFin) : null);
            $proposer->setFrequence($frequence);
            $proposer->setStatut('En attente');
            $proposer->setDateModif((new \DateTime())->format('Y-m-d H:i'));

            $this->proposerRepo->save($proposer);

            $this->addFlash('success', 'Planning ajouté avec succès.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_famille_detail_mvc', ['numFam' => $numFam]);
    }

    #[Route('/admin-mvc/prepa-paie', name: 'admin_prepare_paie_mvc')]
    public function preparationPaie(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        [$year, $month] = $this->parseMoisParam($request->query->get('mois'));
        $moisOffset  = sprintf('%04d-%02d', $year, $month);
        $moisPrev    = (new \DateTime(sprintf('%04d-%02d-01', $year, $month)))->modify('-1 month')->format('Y-m');
        $moisNext    = (new \DateTime(sprintf('%04d-%02d-01', $year, $month)))->modify('+1 month')->format('Y-m');
        $moisCourant = date('Y-m');
        $moisLabel   = (new \DateTime(sprintf('%04d-%02d-01', $year, $month)))->format('F Y');

        $donneesPaie = [];
        foreach ($this->horaireService->getPreparationPaie($year, $month) as $row) {
            $intervenant = $this->intervenantService->getInfosIntervenant($row['numInter']);

            $adrInter = '';
            if ($intervenant) {
                $adrInter = implode(', ', array_filter([
                    $intervenant->getAdresse(),
                    $intervenant->getCodePostal(),
                    $intervenant->getVille(),
                ]));
            }

            $famillesHorsRennes = [];
            foreach ($row['familles'] as $numFam) {
                $fam = $this->familleRepo->findByNumero($numFam);
                if ($fam && !in_array($fam->getCodePostal(), ['35000', '35200', '35700', null, ''], true)) {
                    $famillesHorsRennes[] = implode(', ', array_filter([
                        $fam->getAdresse(),
                        $fam->getCodePostal(),
                        $fam->getVille(),
                    ]));
                }
            }

            if (!$intervenant || !$this->intervenantService->isActif($intervenant)) {
                continue;
            }

            $donneesPaie[] = array_merge($row, [
                'intervenant'        => $intervenant,
                'nomComplet'         => $intervenant->getNom(),
                'adrInter'           => $adrInter,
                'famillesHorsRennes' => $famillesHorsRennes,
            ]);
        }

        return $this->render('admin/hours/paie.html.twig', [
            'auth'        => $this->authService->check(),
            'donneesPaie' => $donneesPaie,
            'moisOffset'  => $moisOffset,
            'moisLabel'   => $moisLabel,
            'moisPrev'    => $moisPrev,
            'moisNext'    => $moisNext,
            'moisCourant' => $moisCourant,
        ]);
    }

    #[Route('/admin-mvc/prepa-paie/csv', name: 'admin_prepare_paie_csv_mvc')]
    public function preparationPaieCsv(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        [$year, $month] = $this->parseMoisParam($request->query->get('mois'));
        $moisLabel = (new \DateTime(sprintf('%04d-%02d-01', $year, $month)))->format('F Y');

        $lines   = [];
        $lines[] = '"Intervenant";"Prestation";"Heures";"Nb prestations";"Km avec enfants"';
        foreach ($this->horaireService->getPreparationPaie($year, $month) as $row) {
            $iv   = $this->intervenantService->getInfosIntervenant($row['numInter']);
            $nom  = $iv ? $iv->getPrenom() . ' ' . $iv->getNom() : '#' . $row['numInter'];
            $type = $row['typePresta'] === 'MENA' ? 'Ménage' : "Garde d'enfants";
            $lines[] = '"' . $nom . '";"' . $type . '";"' . $row['heuresDecimal'] . '";"'
                . $row['nbrTrajet'] . '";"' . str_replace('.', ',', $row['kmAvecEnfant']) . '"';
        }

        $csv = implode("\n", $lines);
        $response = new Response("\xEF\xBB\xBF" . $csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $filename = 'prepa_paie_' . sprintf('%04d-%02d', $year, $month) . '.csv';
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
        return $response;
    }

    #[Route('/admin-mvc/recapitulatif', name: 'admin_recapitulatif_mvc')]
    public function recapitulatifHeures(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter le service de récapitulatif
        $recapitulatif = [];

        return $this->render('admin/hours/recap.html.twig', [
            'auth' => $this->authService->check(),
            'mois' => $mois,
            'recapitulatif' => $recapitulatif,
        ]);
    }

    #[Route('/admin-mvc/recapitulatif/csv', name: 'admin_recapitulatif_csv_mvc')]
    public function recapitulatifCsv(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter la génération CSV
        $csvContent = "Récapitulatif heures - {$mois}\n";
        $csvContent .= "Intervenant,Famille,Date,Heures,Type\n";
        
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"recapitulatif_{$mois}.csv\"");
        
        return $response;
    }

    #[Route('/admin-mvc/fiches-vierges', name: 'admin_fiches_vierges_mvc')]
    public function fichesVierges(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $moisDebut = $request->query->get('mois_debut', date('m/Y'));
        $nbrMois = $request->query->get('nbr_mois', 12);

        return $this->render('admin/sheets/blank.html.twig', [
            'auth' => $this->authService->check(),
            'moisDebut' => $moisDebut,
            'nbrMois' => $nbrMois,
        ]);
    }

    #[Route('/admin-mvc/fiches-vierges/pdf', name: 'admin_fiches_vierges_pdf_mvc', methods: ['POST'])]
    public function fichesViergesPdf(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $type      = strtoupper($request->request->get('type', 'ENFA'));
        $moisDebut = (int)$request->request->get('moisDebut', (int)date('m'));
        $anneeDebut = (int)$request->request->get('anneeDebut', (int)date('Y'));
        $moisFin   = (int)$request->request->get('moisFin', 12);
        $anneeFin  = (int)$request->request->get('anneeFin', (int)date('Y'));

        $moisFr  = ['','Janvier','Février','Mars','Avril','Mai','Juin',
                    'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
        $joursFr = ['','Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];

        // $current représente le mois de facturation (ex: 2026-05-01 = "Mai 2026")
        // La période couvre du 25 du mois précédent au 24 du mois courant
        $current = new \DateTimeImmutable(sprintf('%04d-%02d-01', $anneeDebut, $moisDebut));
        $fin     = new \DateTimeImmutable(sprintf('%04d-%02d-01', $anneeFin,   $moisFin));

        $fiches = [];

        while ($current <= $fin) {

            $prevMonth    = $current->modify('-1 month');
            $periodeDebut = $prevMonth->setDate((int)$prevMonth->format('Y'), (int)$prevMonth->format('m'), 25);
            $periodeFin   = $current->setDate((int)$current->format('Y'), (int)$current->format('m'), 24);

            $rowspanMap = [];
            $tmp = $periodeDebut;

            while ($tmp <= $periodeFin) {
                if ((int)$tmp->format('N') !== 7) {
                    $w = (int)$tmp->format('W');
                    $rowspanMap[$w] = ($rowspanMap[$w] ?? 0) + 1;
                }
                $tmp = $tmp->modify('+1 day');
            }

            $jours = [];
            $tmp = $periodeDebut;
            $prevWeek = null;

            while ($tmp <= $periodeFin) {

                $dow  = (int)$tmp->format('N');
                $week = (int)$tmp->format('W');

                $isDim   = $dow === 7;
                $newWeek = !$isDim && $week !== $prevWeek;

                if ($newWeek) {
                    $prevWeek = $week;
                }

                $jours[] = [
                    'isDimanche'      => $isDim,
                    'nouvelleSemaine' => $newWeek,
                    'rowspan'         => $newWeek ? ($rowspanMap[$week] ?? 1) : 0,
                    'numeroSemaine'   => $week,
                    'nomJourCourt'    => $joursFr[$dow],
                    'numeroJour'      => (int)$tmp->format('d'),
                    'afficherMois'    => (int)$tmp->format('d') === 1
                                        || (int)$tmp->format('d') === 25,
                    'nomMois'         => substr($moisFr[(int)$tmp->format('m')], 0, 3),
                ];

                $tmp = $tmp->modify('+1 day');
            }

            $fiches[] = [
                'moisComplet' => $moisFr[(int)$current->format('m')],
                'annee'       => (int)$current->format('Y'),
                'dateLimite'  => '24',
                'jours'       => $jours,
                'afficherKm'  => $type === 'ENFA',
            ];

            $current = $current->modify('+1 month');
        }

        return $this->render('admin/sheets/generate.html.twig', [
            'auth'        => $this->authService->check(),
            'fiches'      => $fiches,
            'type'        => $type,
            'typeLibelle' => $type === 'ENFA' ? "GARDES D'ENFANTS" : 'MÉNAGES',
        ]);
    }

    #[Route('/admin-mvc/prestations', name: 'admin_signalements_mvc')]
    public function prestationsMois(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $moisOffset = (int)$request->query->get('mois', 0);
        $refDate    = (new \DateTime('first day of this month'))->modify("{$moisOffset} months");
        $year       = (int)$refDate->format('Y');
        $month      = (int)$refDate->format('m');

        $horairesMois = $this->horaireRepo->findAllForMonth($year, $month);
        $indexH = [];
        foreach ($horairesMois as $h) {
            // Normalisation : trim + uppercase pour éviter les faux-négatifs sur les espaces ou la casse
            $key = trim((string)$h->getNumFam())
                . '|' . $h->getDatePresta()->format('Y-m-d')
                . '|' . strtoupper(trim((string)$h->getTypePresta()))
                . '|' . (int)$h->getNumInter();
            $indexH[$key] = $h;
        }

        $familles = [];
        // findWithPlanningForMonth filtre sur le mois sélectionné, pas sur CURDATE()
        foreach ($this->familleRepo->findWithPlanningForMonth($year, $month) as $famille) {
            $numFam      = $famille->getNumeroFamille();
            $occurrences = $this->proposerRepo->expandForMonth($numFam, $year, $month);
            if (empty($occurrences)) {
                continue;
            }

            $nbCreneaux   = count($occurrences);
            $nbInter      = 0;
            $nbFam        = 0;
            $nbEcarts     = 0;
            // Tous les intervenants prévus, qu'ils aient saisi des heures ou non
            $allInterIds  = [];

            foreach ($occurrences as ['date' => $date, 'proposer' => $proposer]) {
                $allInterIds[$proposer->getNumSalarie()] = true;

                $key = trim((string)$numFam)
                    . '|' . $date->format('Y-m-d')
                    . '|' . strtoupper(trim($proposer->getTypePrestation()))
                    . '|' . $proposer->getNumSalarie();
                $h = $indexH[$key] ?? null;

                if ($h && $h->getHeureDebutPresta()) { $nbInter++; }
                if ($h && $h->getHeureDebutFam())    { $nbFam++; }

                if ($h
                    && $h->getHeureDebutPresta() && $h->getHeureFinPresta()
                    && $h->getHeureDebutFam()    && $h->getHeureFinFam()
                ) {
                    $durI = $this->minutesDiff($h->getHeureDebutPresta(), $h->getHeureFinPresta());
                    $durF = $this->minutesDiff($h->getHeureDebutFam(),    $h->getHeureFinFam());
                    if ($durI !== $durF) { $nbEcarts++; }
                }
            }

            $intervenantsNoms = [];
            foreach (array_keys($allInterIds) as $interId) {
                $iv = $this->intervenantRepo->find((int) $interId);
                if ($iv) {
                    $intervenantsNoms[] = $iv->getNomCompletInter();
                }
            }

            $familles[] = [
                'numFam'           => $numFam,
                'nomFam'           => $famille->getNomFamille() ?? $numFam,
                'nbCreneaux'       => $nbCreneaux,
                'nbInter'          => $nbInter,
                'nbFam'            => $nbFam,
                'nbEcarts'         => $nbEcarts,
                'intervenantsNoms' => $intervenantsNoms,
            ];
        }

        return $this->render('admin/hours/disputes.html.twig', [
            'auth'       => $this->authService->check(),
            'familles'   => $familles,
            'moisOffset' => $moisOffset,
            'moisLabel'  => $refDate->format('F Y'),
        ]);
    }

    #[Route('/admin-mvc/prestations/{numFam}', name: 'admin_prestations_famille_mvc')]
    public function prestationsFamille(string $numFam, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleRepo->find($numFam);
        if (!$famille || $famille->getArchive()) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $ecarts = $this->horaireService->getSignalementsFamille($numFam);

        $intervenantsMap = [];
        foreach ($ecarts as $h) {
            $id = $h->getNumInter();
            if ($id && !isset($intervenantsMap[$id])) {
                $intervenantsMap[$id] = $this->intervenantService->getInfosIntervenant($id);
            }
        }

        return $this->render('admin/hours/disputes_detail.html.twig', [
            'auth'        => $this->authService->check(),
            'famille'     => $famille,
            'ecarts'      => array_values($ecarts),
            'intervenants' => $intervenantsMap,
        ]);
    }

    #[Route('/admin-mvc/prestation/{id}/source', name: 'admin_choisir_source_mvc', methods: ['POST'])]
    public function choisirSource(int $id, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $source = $request->request->get('source');
        $numFam = $request->request->get('numFam', '');
        $this->horaireService->choisirSourceFacturation($id, $source);

        if ($numFam) {
            return $this->redirectToRoute('admin_prestations_famille_mvc', ['numFam' => $numFam]);
        }
        return $this->redirectToRoute('admin_signalements_mvc');
    }

    #[Route('/admin-mvc/exceptions-facturation', name: 'admin_exceptions_facturation_mvc')]
    public function exceptionsFacturation(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $exceptions = $this->releveMensuelFamilleService->getToutesLesExceptions();

        return $this->render('admin/exceptions-facturation.html.twig', [
            'auth'       => $this->authService->check(),
            'exceptions' => $exceptions,
        ]);
    }

    // ── Factures ──────────────────────────────────────────────────────────────

    #[Route('/admin-mvc/factures', name: 'admin_factures_mvc')]
    public function factures(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $moisAnnee = $request->query->get('mois', date('Y-m'));
        $familles  = $this->familleService->getToutesLesFamilles();

        return $this->render('admin/factures/index.html.twig', [
            'auth'      => $this->authService->check(),
            'familles'  => $familles,
            'moisAnnee' => $moisAnnee,
        ]);
    }

    #[Route('/admin-mvc/factures/{numFam}/{moisAnnee}', name: 'admin_facture_detail_mvc')]
    public function factureDetail(string $numFam, string $moisAnnee, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        $facture = $this->factureService->calculerFacture($numFam, $moisAnnee);

        return $this->render('admin/factures/detail.html.twig', [
            'auth'      => $this->authService->check(),
            'famille'   => $famille,
            'facture'   => $facture,
            'moisAnnee' => $moisAnnee,
        ]);
    }

    #[Route('/admin-mvc/factures/{numFam}/{moisAnnee}/imprimer', name: 'admin_facture_print_mvc')]
    public function facturePrint(string $numFam, string $moisAnnee): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        $facture = $this->factureService->calculerFacture($numFam, $moisAnnee);

        return $this->render('admin/factures/print.html.twig', [
            'famille'   => $famille,
            'facture'   => $facture,
            'moisAnnee' => $moisAnnee,
        ]);
    }

    #[Route('/admin-mvc/factures/imprimer-toutes', name: 'admin_factures_print_all_mvc')]
    public function imprimerToutesFactures(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $moisAnnee = $request->query->get('mois', date('Y-m'));
        $familles  = $this->familleService->getToutesLesFamilles();

        $factures = [];
        foreach ($familles as $famille) {
            $factures[] = [
                'famille' => $famille,
                'facture' => $this->factureService->calculerFacture($famille->getNumeroFamille(), $moisAnnee),
            ];
        }

        return $this->render('admin/factures/print_all.html.twig', [
            'factures'  => $factures,
            'moisAnnee' => $moisAnnee,
        ]);
    }

    // ── Formulaires de vacances ───────────────────────────────────────────────

    #[Route('/admin-mvc/vacances', name: 'admin_vacances_mvc')]
    public function vacances(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('admin/vacances.html.twig', [
            'auth'    => true,
            'configs' => $this->vacancesRepo->findAllOrderedDesc(),
            'actif'   => $this->vacancesRepo->findActif(),
        ]);
    }

    #[Route('/admin-mvc/vacances/creer', name: 'admin_vacances_creer_mvc', methods: ['POST'])]
    public function creerVacances(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('vacances_creer', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_vacances_mvc');
        }

        $titre        = trim($request->request->get('titre', ''));
        $periode      = trim($request->request->get('periodeTexte', ''));
        $message      = trim($request->request->get('message', ''));
        $moisPeriodes = trim($request->request->get('moisPeriodes', ''));
        $dateDebutStr = trim($request->request->get('dateApparitionDebut', ''));
        $dateFinStr   = trim($request->request->get('dateApparitionFin', ''));

        if (!$titre) {
            $this->addFlash('error', 'Le titre est obligatoire.');
            return $this->redirectToRoute('admin_vacances_mvc');
        }

        $config = new VacancesConfig();
        $config->setTitre($titre);
        $config->setPeriodeTexte($periode ?: null);
        $config->setMessage($message ?: null);
        $config->setMoisPeriodes($moisPeriodes ?: null);
        $config->setDateApparitionDebut($dateDebutStr ? new \DateTimeImmutable($dateDebutStr) : null);
        $config->setDateApparitionFin($dateFinStr ? new \DateTimeImmutable($dateFinStr) : null);
        $config->setActif(false);

        $this->em->persist($config);
        $this->em->flush();

        $this->addFlash('success', 'Formulaire de vacances créé.');
        return $this->redirectToRoute('admin_vacances_mvc');
    }

    #[Route('/admin-mvc/vacances/{id}/toggle', name: 'admin_vacances_toggle_mvc', methods: ['POST'])]
    public function toggleVacances(int $id, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('vacances_toggle_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_vacances_mvc');
        }

        $config = $this->vacancesRepo->find($id);
        if (!$config) {
            $this->addFlash('error', 'Configuration introuvable.');
            return $this->redirectToRoute('admin_vacances_mvc');
        }

        if ($config->isActif()) {
            /* Désactiver */
            $config->setActif(false);
            $this->addFlash('success', 'Formulaire de vacances désactivé.');
        } else {
            /* Activer : désactiver les autres d'abord */
            $this->vacancesRepo->desactiverTout();
            $this->em->refresh($config);
            $config->setActif(true);
            $this->addFlash('success', 'Formulaire de vacances activé sur tous les relevés.');
        }

        $this->em->flush();
        return $this->redirectToRoute('admin_vacances_mvc');
    }

    #[Route('/admin-mvc/vacances/{id}/supprimer', name: 'admin_vacances_supprimer_mvc', methods: ['POST'])]
    public function supprimerVacances(int $id, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('vacances_supprimer_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_vacances_mvc');
        }

        $config = $this->vacancesRepo->find($id);
        if ($config) {
            $this->em->remove($config);
            $this->em->flush();
            $this->addFlash('success', 'Configuration supprimée.');
        }

        return $this->redirectToRoute('admin_vacances_mvc');
    }

    // ── Configuration générale ────────────────────────────────────────────────

    #[Route('/admin-mvc/configuration', name: 'admin_config_mvc')]
    public function configuration(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $config = $this->appConfigRepo->getConfig();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_config', $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('admin_config_mvc');
            }
            $config->setNbrJourSaisie((int)$request->request->get('nbrJourSaisie', 10));
            $config->setNbrPalierTarifGE((int)$request->request->get('nbrPalierTarifGE', 4));
            $config->setNbrPalierTarifM((int)$request->request->get('nbrPalierTarifM', 0));
            $config->touch();
            $this->em->flush();
            $this->addFlash('success', 'Configuration enregistrée.');
            return $this->redirectToRoute('admin_config_mvc');
        }

        return $this->render('admin/configuration.html.twig', [
            'auth'   => $this->authService->check(),
            'config' => $config,
        ]);
    }

    #[Route('/admin-mvc/releves-intervenants', name: 'admin_releves_intervenants_mvc')]
    public function relevesIntervenants(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        // Mois sélectionné (format YYYY-MM) ou mois courant par défaut
        $moisParam = $request->query->get('mois');
        if ($moisParam && preg_match('/^\d{4}-\d{2}$/', $moisParam)) {
            [$y, $m] = array_map('intval', explode('-', $moisParam));
        } else {
            $today = new \DateTime('today');
            $d     = (int)$today->format('d');
            // Si on est après le 24, le mois de facturation courant est le mois suivant
            $ref   = $d >= 25 ? (clone $today)->modify('+1 month') : $today;
            $y     = (int)$ref->format('Y');
            $m     = (int)$ref->format('m');
        }

        // Période : 25 du mois précédent → 24 du mois $m/$y
        $prev  = (new \DateTime(sprintf('%04d-%02d-01', $y, $m)))->modify('-1 month');
        $debut = new \DateTime(sprintf('%04d-%02d-25', (int)$prev->format('Y'), (int)$prev->format('m')));
        $fin   = new \DateTime(sprintf('%04d-%02d-24', $y, $m));

        $moisAnnee    = sprintf('%02d/%04d', $m, $y);
        $intervenants = $this->intervenantService->getTousLesIntervenants();

        // Types de prestation par intervenant (depuis proposer actifs — connexion principal)
        $typesParInter = [];
        $connPrincipal = $this->em->getConnection();
        // Utiliser la connexion principal via le registry
        $rows = $this->proposerRepo->getEntityManager()->getConnection()->executeQuery(
            "SELECT DISTINCT numSalarie_Intervenants AS numInter, idPresta_Prestations AS typePresta
             FROM proposer
             WHERE idADH_TypeADH = 'PREST'
               AND (idPresta_Prestations = 'MENA' OR idPresta_Prestations = 'ENFA')
               AND (dateFin_Proposer IS NULL
                    OR dateFin_Proposer = '0000-00-00'
                    OR dateFin_Proposer >= CURDATE())"
        )->fetchAllAssociative();
        foreach ($rows as $row) {
            $typesParInter[(int)$row['numInter']][strtoupper($row['typePresta'])] = true;
        }

        $validFamIds  = $this->familleRepo->findAllValidFamilleIds();

        // Périodes par type :
        //   - MENA : 25 du mois précédent → 24 du mois courant ($debut/$fin)
        //   - ENFA : 1er → dernier jour du mois calendaire
        $enfaDebut = new \DateTime(sprintf('%04d-%02d-01', $y, $m));
        $enfaFin   = (clone $enfaDebut)->modify('last day of this month');

        $statsMena = $this->horaireRepo->getStatsPeriodeParIntervenant(
            $debut->format('Y-m-d'), $fin->format('Y-m-d'), $validFamIds, 'MENA'
        );
        $statsEnfa = $this->horaireRepo->getStatsPeriodeParIntervenant(
            $enfaDebut->format('Y-m-d'), $enfaFin->format('Y-m-d'), $validFamIds, 'ENFA'
        );

        // Fusion des deux jeux de stats par intervenant
        $stats = [];
        foreach ([$statsMena, $statsEnfa] as $part) {
            foreach ($part as $id => $data) {
                foreach (['MENA', 'ENFA'] as $t) {
                    if (isset($data[$t])) {
                        $stats[$id][$t] = $data[$t];
                    }
                }
                $prevAjout = $stats[$id]['derniereAjout'] ?? '0000-00-00 00:00:00';
                $newAjout  = $data['derniereAjout'] ?? '0000-00-00 00:00:00';
                $stats[$id]['derniereAjout'] = $newAjout > $prevAjout ? $newAjout : $prevAjout;
            }
        }

        // Inclure aussi les intervenants ayant des prestations déclarées (ex. occasionnel)
        // mais sans planning PREST → absents de getTousLesIntervenants().
        $presentIds = [];
        foreach ($intervenants as $iv) {
            $presentIds[(int) $iv->getId()] = true;
        }
        foreach (array_keys($stats) as $id) {
            if (!isset($presentIds[$id])) {
                $extra = $this->intervenantRepo->find($id);
                if ($extra) {
                    $intervenants[] = $extra;
                    $presentIds[$id] = true;
                }
            }
        }

        // Relevés depuis relevemensuelinter pour ce mois
        $relevesMois = $this->relevemensuelinterRepo->findBy(['moisannee' => $moisAnnee]);
        $releves = [];
        foreach ($relevesMois as $r) {
            $releves[$r->getNumInter()][$r->getTypePresta()] = [
                'signer'        => $r->isSigner(),
                'signerLe'      => $r->getSignerLe(),
                'telechargerLe' => $r->getTelechargerLe(),
            ];
        }

        // Trier : ceux qui ont des heures en premier
        usort($intervenants, function($a, $b) use ($stats) {
            $idA  = $a->getId() ?? 0;
            $idB  = $b->getId() ?? 0;
            $totA = ($stats[$idA]['MENA'] ?? 0) + ($stats[$idA]['ENFA'] ?? 0);
            $totB = ($stats[$idB]['MENA'] ?? 0) + ($stats[$idB]['ENFA'] ?? 0);
            return $totB <=> $totA;
        });

        // Générer les mois disponibles pour le sélecteur (12 mois + mois suivant si après le 24)
        $moisDisponibles = [];
        $moisFr   = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
        $todayDay = (int)(new \DateTime())->format('d');

        // Après le 24 : la période de facturation du mois suivant a commencé → l'ajouter en tête
        if ($todayDay >= 25) {
            $next = (new \DateTime('first day of this month'))->modify('+1 month');
            $moisDisponibles[] = [
                'value' => $next->format('Y-m'),
                'label' => $moisFr[(int)$next->format('m')] . ' ' . $next->format('Y'),
            ];
        }

        // Toujours inclure le mois courant et les 11 mois précédents
        for ($i = 0; $i < 12; $i++) {
            $dt = (new \DateTime('first day of this month'))->modify("-$i month");
            $moisDisponibles[] = [
                'value' => $dt->format('Y-m'),
                'label' => $moisFr[(int)$dt->format('m')] . ' ' . $dt->format('Y'),
            ];
        }

        // moisOffset pour les liens PDF individuels
        $now        = new \DateTime('first day of this month');
        $ref        = new \DateTime(sprintf('%04d-%02d-01', $y, $m));
        $diffMonths = (($y - (int)$now->format('Y')) * 12) + ($m - (int)$now->format('m'));

        return $this->render('admin/releves/intervenants.html.twig', [
            'auth'            => $this->authService->check(),
            'intervenants'    => $intervenants,
            'stats'           => $stats,
            'releves'         => $releves,
            'typesParInter'   => $typesParInter,
            'periodeDebut'    => $debut,
            'periodeFin'      => $fin,
            'moisActuel'      => sprintf('%04d-%02d', $y, $m),
            'moisOffset'      => $diffMonths,
            'moisDisponibles' => $moisDisponibles,
        ]);
    }

    #[Route('/admin-mvc/releves-batch', name: 'admin_releves_batch_mvc')]
    public function relevesBatch(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $ids    = $request->query->all('ids');
        $type   = strtoupper($request->query->get('type', 'MENA'));
        $types  = $type === 'ALL' ? ['MENA', 'ENFA'] : [$type];

        // Mois sélectionné transmis depuis la page admin (format YYYY-MM)
        $moisParam = $request->query->get('mois');
        if ($moisParam && preg_match('/^\d{4}-\d{2}$/', $moisParam)) {
            [$yBatch, $mBatch] = array_map('intval', explode('-', $moisParam));
        } else {
            $today  = new \DateTime('today');
            $yBatch = (int)$today->format('Y');
            $mBatch = (int)$today->format('m');
        }
        $moisAnneeBatch = sprintf('%02d/%04d', $mBatch, $yBatch);

        // Calcul du moisOffset par rapport au mois courant
        $now        = new \DateTime('first day of this month');
        $ref        = new \DateTime(sprintf('%04d-%02d-01', $yBatch, $mBatch));
        $moisOffset = (int)$now->diff($ref)->m * ($ref < $now ? -1 : 1)
                    + (int)$now->diff($ref)->y * 12 * ($ref < $now ? -1 : 1);

        $joursFr = ['', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $moisFr  = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        $fmt     = static fn(int $sec): string => number_format($sec / 3600, 2, ',', '');

        $fiches = [];

        foreach ($ids as $rawId) {
            $id   = (int)$rawId;
            if (!$id) continue;
            $user = $this->intervenantService->getInfosIntervenant($id);
            if (!$user) continue;

            foreach ($types as $t) {
                $releveData = $this->horaireService->getReleveData($id, $t, $moisOffset, $user);

                // skip si pas d'heures
                if (empty($releveData['familles'])) continue;

                // Marquer téléchargé avec le bon mois
                $this->relevemensuelinterRepo->marquerTelecharge($moisAnneeBatch, $id, $t);

                // Préparer jours
                $rowspanMap = [];
                foreach ($releveData['jours'] as $j) {
                    $d = new \DateTimeImmutable($j['date']);
                    if ((int)$d->format('N') !== 7) {
                        $w = (int)$d->format('W');
                        $rowspanMap[$w] = ($rowspanMap[$w] ?? 0) + 1;
                    }
                }

                $prevWeek = null;
                $jours    = [];
                foreach ($releveData['jours'] as $j) {
                    $d = new \DateTimeImmutable($j['date']);
                    $dow = (int)$d->format('N');
                    $week = (int)$d->format('W');
                    $isDim = $dow === 7;
                    $newW  = !$isDim && $week !== $prevWeek;
                    if ($newW) $prevWeek = $week;
                    $jours[] = [
                        'date' => $j['date'], 'isDimanche' => $isDim,
                        'nouvelleSemaine' => $newW,
                        'rowspan'         => $newW ? ($rowspanMap[$week] ?? 1) : 0,
                        'numeroSemaine'   => $week,
                        'nomJourCourt'    => $joursFr[$dow],
                        'numeroJour'      => (int)$d->format('j'),
                        'afficherMois'    => (int)$d->format('j') === 1,
                        'nomMois'         => $moisFr[(int)$d->format('m')],
                    ];
                }

                $familles  = array_map(static fn($f) => array_merge($f, ['totalHeures' => $fmt($f['totalSecondes'] ?? 0)]), $releveData['familles']);
                $totalSec  = array_sum(array_column($familles, 'totalSecondes'));
                $typeLabel = $t === 'MENA' ? 'Menage' : 'GardeEnfants';

                $fiches[] = [
                    'intervenant'   => $releveData['intervenant'],
                    'type'          => $t,
                    'typeLibelle'   => $t === 'ENFA' ? "GARDES D'ENFANTS" : 'MÉNAGES',
                    'periode'       => $releveData['periode'],
                    'jours'         => $jours,
                    'famillesPages' => array_chunk($familles, 5),
                    'totaux'        => $releveData['totaux'],
                    'totalHeures'   => $fmt($totalSec),
                    'signer'        => $releveData['signer'],
                    'afficherKm'    => $t === 'ENFA',
                    'heureDehors'   => $releveData['heureDehors'] ?? null,
                    'filename'      => "Releve_{$typeLabel}_{$releveData['periode']['mois']}_{$releveData['periode']['anner']}.pdf",
                ];
            }
        }

        $filename = count($fiches) === 1
            ? $fiches[0]['filename']
            : 'Releves_' . date('m_Y') . '.pdf';

        return $this->render('admin/releves/batch.html.twig', [
            'fiches'   => $fiches,
            'filename' => $filename,
            'type'     => $type,
        ]);
    }

    /**
     * Durée en minutes entre deux DateTime de type `time`.
     * Gère le passage à minuit (fin < début → +24h).
     */
    private function minutesDiff(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        $secs = $to->getTimestamp() - $from->getTimestamp();
        if ($secs < 0) {
            $secs += 86400; // passage minuit
        }
        return (int)floor($secs / 60);
    }

    private function parseMoisParam(?string $param): array
    {
        if ($param && preg_match('/^\d{4}-\d{2}$/', $param)) {
            [$y, $m] = array_map('intval', explode('-', $param));
            return [$y, $m];
        }
        return [(int)date('Y'), (int)date('m')];
    }
}
