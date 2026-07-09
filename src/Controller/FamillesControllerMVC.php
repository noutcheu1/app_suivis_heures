<?php

namespace App\Controller;

use App\Repository\HoraireinterRepository;
use App\Repository\ParentFamilleRepository;
use App\Repository\ProposerRepository;
use App\Repository\VacancesConfigRepository;
use App\Repository\VacancesReponseFamilleRepository;
use App\Service\AuthService;
use App\Service\FactureService;
use App\Service\FamilleIntervenantService;
use App\Service\FamilleService;
use App\Service\HoraireinterService;
use App\Service\IntervenantService;
use App\Service\ReleveFamilleBuilder;
use App\Service\ReleveMensuelFamilleService;
use App\Repository\TarifFamilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la gestion des familles selon le pattern MVC
 */
final class FamillesControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService                 $authService,
        private FamilleService              $familleService,
        private HoraireinterService         $horaireService,
        private FamilleIntervenantService   $familleIntervenantService,
        private ReleveMensuelFamilleService $releveMensuelFamilleService,
        private FactureService              $factureService,
        private ReleveFamilleBuilder        $releveFamilleBuilder,
        private VacancesConfigRepository    $vacancesRepo,
        private VacancesReponseFamilleRepository $reponseFamRepo,
        private IntervenantService          $intervenantService,
        private ProposerRepository          $proposerRepo,
        private HoraireinterRepository      $horaireRepo,
        private ParentFamilleRepository     $parentRepo,
        private \App\Repository\FamilleTokenRepository $tokenRepo,
    ) {}

    #[Route('/famille/mon-espace', name: 'famille_panel_mvc')]
    public function panel(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->authService->isAdmin()) {
            return $this->redirectToRoute('admin_dashboard_mvc');
        }

        $numFam = $this->authService->famille_id();
        if (!$numFam) {
            throw $this->createNotFoundException(
                'Aucun dossier famille trouvé pour votre identifiant. Contactez l\'administrateur.'
            );
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $prestationsNonValidees = $this->horaireService->getPrestationsNonDeclarees($numFam);
        $prestationsValidees    = $this->horaireService->getPrestationsParFamille($numFam);
        $stats                  = $this->horaireService->getDashboardStatsFamille($numFam);
        $dernieres              = array_slice($prestationsValidees, 0, 5);

        // Uniquement les intervenants assignés à cette famille dans proposer
        $intervenants  = $this->familleIntervenantService->getIntervenantsForFamille($numFam);
        $assignations  = $this->familleIntervenantService->getAssignationsFamille($numFam);

        // Dernière campagne de congés pour cette famille (pour la carte + bouton « Répondre »).
        $reponseVacances  = $this->reponseFamRepo->findLatestByNumFam($numFam);
        $campagneVacances = $reponseVacances
            ? $this->vacancesRepo->find($reponseVacances->getVacancesConfigId())
            : null;

        return $this->render('familles/dashboard.html.twig', [
            'auth'                   => true,
            'famille'                => $famille,
            'prestationsNonValidees' => $prestationsNonValidees,
            'prestationsValidees'    => $prestationsValidees,
            'stats'                  => $stats,
            'dernieres'              => $dernieres,
            'intervenants'           => $intervenants,
            'assignations'           => $assignations,
            'reponseVacances'        => $reponseVacances,
            'campagneVacances'       => $campagneVacances,
            'qrToken'                => $this->tokenRepo->tokenPour((string) $numFam),
        ]);
    }


    #[Route('/famille/adminpanel/{numFam}', name: 'famille_admin_panel_mvc')]
    public function adminpanel(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        
        
        if (!$numFam) {
            throw $this->createNotFoundException(
                'Aucun dossier famille trouvé pour votre identifiant. Contactez l\'administrateur.'
            );
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $prestationsNonValidees = $this->horaireService->getPrestationsNonDeclarees($numFam);
        $prestationsValidees    = $this->horaireService->getPrestationsParFamille($numFam);
        $stats                  = $this->horaireService->getDashboardStatsFamille($numFam);
        $dernieres              = array_slice($prestationsValidees, 0, 5);

        // Uniquement les intervenants assignés à cette famille dans proposer
        $intervenants  = $this->familleIntervenantService->getIntervenantsForFamille($numFam);
        $assignations  = $this->familleIntervenantService->getAssignationsFamille($numFam);

        // Dernière campagne de congés pour cette famille (pour la carte + bouton « Répondre »).
        $reponseVacances  = $this->reponseFamRepo->findLatestByNumFam($numFam);
        $campagneVacances = $reponseVacances
            ? $this->vacancesRepo->find($reponseVacances->getVacancesConfigId())
            : null;

        return $this->render('familles/dashboard.html.twig', [
            'auth'                   => true,
            'famille'                => $famille,
            'prestationsNonValidees' => $prestationsNonValidees,
            'prestationsValidees'    => $prestationsValidees,
            'stats'                  => $stats,
            'dernieres'              => $dernieres,
            'intervenants'           => $intervenants,
            'assignations'           => $assignations,
            'reponseVacances'        => $reponseVacances,
            'campagneVacances'       => $campagneVacances,
            'qrToken'                => $this->tokenRepo->tokenPour((string) $numFam),
        ]);
    }

    #[Route('/famille/mon-profil', name: 'famille_profile_mvc')]
    public function familleProfile(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $numFam = $this->authService->isAdmin()
            ? $request->query->get('numFam')
            : $this->authService->famille_id();
        if (!$numFam) {
            throw $this->createNotFoundException(
                'Aucun dossier famille trouvé pour votre identifiant. Contactez l\'administrateur.'
            );
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $parents = $this->parentRepo->findByFamille($numFam);

        return $this->render('familles/profile.html.twig', [
            'auth'    => true,
            'famille' => $famille,
            'parents' => $parents,
        ]);
    }

    #[Route('/famille/mon-qrcode', name: 'famille_qr_mvc')]
    public function qrCode(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        // Admin : consulte le QR d'une famille via ?token= (jamais le numéro en clair).
        // Famille : toujours sa propre fiche (paramètre ignoré → pas d'énumération).
        if ($this->authService->isAdmin()) {
            $numFam = ($t = $request->query->get('token')) ? $this->tokenRepo->numFamPour($t) : null;
        } else {
            $numFam = $this->authService->famille_id();
        }
        if (!$numFam) {
            throw $this->createNotFoundException(
                'Aucun dossier famille trouvé pour votre identifiant. Contactez l\'administrateur.'
            );
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        if (!$famille) throw $this->createNotFoundException('Famille introuvable');

        return $this->render('familles/qr-code.html.twig', [
            'auth'    => true,
            'famille' => $famille,
            'qrToken' => $this->tokenRepo->tokenPour((string) $numFam),
        ]);
    }

    #[Route('/famille/mes-signalements', name: 'famille_signalements_mvc')]
    public function signalements(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        $numFam = $this->authService->isAdmin()
            ? $request->query->get('numFam')
            : $this->authService->famille_id();
        if (!$numFam) {
            throw $this->createNotFoundException(
                'Aucun dossier famille trouvé pour votre identifiant. Contactez l\'administrateur.'
            );
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        if (!$famille) throw $this->createNotFoundException('Famille introuvable');

        $signalements = $this->horaireService->getSignalementsFamille($numFam);

        return $this->render('familles/signalements.html.twig', [
            'auth'        => true,
            'famille'     => $famille,
            'signalements'=> $signalements,
        ]);
    }

    #[Route('/familles-mvc', name: 'familles_mvc')]
    public function liste(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $familles = $this->familleService->getToutesLesFamilles();

        return $this->render('admin/familles/list.html.twig', [
            'auth' => $this->authService->check(),
            'users' => $familles,
        ]);
    }

    #[Route('/familles-mvc/{numFam}', name: 'famille_detail_mvc')]
    public function detail(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->authService->isAdmin() && $this->authService->famille_id() !== $numFam) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $isAdmin = $this->authService->isAdmin();
        $now     = new \DateTime();
        $year    = (int)$now->format('Y');
        $month   = (int)$now->format('m');

        // Uniquement les prestations du mois courant non encore déclarées par la famille
        $horairesBruts = array_filter(
            $this->horaireRepo->findByFamilleForMonth($numFam, $year, $month),
            fn($h) => $h->getDeclarerLeFam() === null && !$h->isDesactiver()
        );

        // Index des proposers actifs par (numSalarie|jour) pour retrouver les heures planifiées
        $jourMap = [
            1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi',
            5 => 'vendredi', 6 => 'samedi', 7 => 'dimanche',
        ];
        $proposerIndex = [];
        foreach ($this->proposerRepo->findActivesByFamille($numFam) as $p) {
            $key = $p->getNumSalarie() . '|' . mb_strtolower(trim($p->getJour()));
            $proposerIndex[$key] = $p;
        }

        // Charger les noms des intervenants concernés
        $intervenantsMap = [];
        foreach ($horairesBruts as $h) {
            $id = $h->getNumInter();
            if ($id && !isset($intervenantsMap[$id])) {
                $intervenantsMap[$id] = $this->intervenantService->getInfosIntervenant($id);
            }
        }

        // Associer chaque horaireinter à son proposer (heures planifiées)
        $lignes = [];
        foreach ($horairesBruts as $h) {
            $jour   = $jourMap[(int)$h->getDatePresta()->format('N')] ?? '';
            $key    = $h->getNumInter() . '|' . $jour;
            $lignes[] = [
                'horaire'      => $h,
                'proposer'     => $proposerIndex[$key] ?? null,
                'intervenant'  => $intervenantsMap[$h->getNumInter()] ?? null,
            ];
        }

        // Trier par date puis heure de début
        usort($lignes, fn($a, $b) =>
            $a['horaire']->getDatePresta() <=> $b['horaire']->getDatePresta()
            ?: ($a['horaire']->getHeureDebutPresta() <=> $b['horaire']->getHeureDebutPresta())
        );

        return $this->render('familles/detail.html.twig', [
            'auth'       => $this->authService->check(),
            'famille'    => $famille,
            'lignes'     => $lignes,
            'moisLabel'  => $now->format('F Y'),
            'isAdmin'    => $isAdmin,
        ]);
    }

    #[Route('/familles-mvc/{numFam}/declarer/{id}', name: 'famille_declarer_heures_mvc', methods: ['POST'])]
    public function declarerHeures(string $numFam, int $id, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->authService->isAdmin() && $this->authService->famille_id() !== $numFam) {
            return $this->redirectToRoute('app_login');
        }

        $heureDebut = $request->request->get('heureDebut');
        $heureFin   = $request->request->get('heureFin');

        if ($this->horaireService->declarerHeuresFam($id, $numFam, $heureDebut, $heureFin)) {
            $this->addFlash('success', 'Heures confirmées');
        } else {
            $this->addFlash('error', 'Impossible d\'enregistrer ces heures');
        }

        return $this->redirectToRoute('famille_detail_mvc', ['numFam' => $numFam]);
    }

    #[Route('/familles-mvc/{numFam}/declarer-nouvelle', name: 'famille_declarer_nouvelle_mvc', methods: ['POST'])]
    public function declarerNouvellePrestation(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->authService->isAdmin() && $this->authService->famille_id() !== $numFam) {
            return $this->redirectToRoute('app_login');
        }

        $heureDebut   = $request->request->get('heureDebut');
        $heureFin     = $request->request->get('heureFin');
        $dateStr      = $request->request->get('date');
        $typePresta   = $request->request->get('typePresta');
        $numInter     = (int)$request->request->get('numInter');
        $nomFam       = $request->request->get('nomFam', '');
        $moisOffset   = (int)$request->request->get('moisOffset', 0);

        $this->horaireService->declarerNouvellePresation(
            $numFam, $nomFam, $numInter, $dateStr, $typePresta, $heureDebut, $heureFin
        );

        $this->addFlash('success', 'Heures enregistrées');
        return $this->redirectToRoute('famille_detail_mvc', ['numFam' => $numFam, 'mois' => $moisOffset]);
    }

    #[Route('/familles-mvc/{numFam}/releves', name: 'famille_releves_mvc')]
    public function releves(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->authService->isAdmin() && $this->authService->famille_id() !== $numFam) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $historique = $this->releveMensuelFamilleService->getHistoriqueReleves($numFam);

        return $this->render('familles/releves.html.twig', [
            'auth'           => $this->authService->check(),
            'famille'        => $famille,
            'historique'     => $historique,
            'vacancesConfig' => $this->vacancesRepo->findActif(),
        ]);
    }

    #[Route('/familles-mvc/{numFam}/estimation-facture', name: 'famille_estimation_facture_mvc')]
    public function estimationFacture(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('Y-m'));

        return $this->redirectToRoute('famille_ma_facture_mvc', ['mois' => $mois]);
    }

    #[Route('/familles-mvc/{numFam}/avis', name: 'famille_avis_mvc', methods: ['POST'])]
    public function donnerAvis(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->authService->isAdmin() && $this->authService->famille_id() !== $numFam) {
            return $this->redirectToRoute('app_login');
        }

        $moisAnnee  = $request->request->get('moisAnnee', date('Y-m'));
        $typePresta = strtoupper($request->request->get('typePresta', 'ENFA'));

        $this->releveMensuelFamilleService->sauvegarderQuestionnaire($numFam, $moisAnnee, [$typePresta], [
            'avisPonctualite' => $request->request->get('ponctualite'),
            'avisReguRela'    => $request->request->get('relationnel'),
            'avisRespectHo'   => $request->request->get('respectHoraires'),
            'avisQualiteTr'   => $request->request->get('qualiteTravail'),
        ]);

        $this->addFlash('success', 'Votre avis a été enregistré avec succès.');

        return $this->redirectToRoute('famille_detail_mvc', ['numFam' => $numFam]);
    }

    // ── Admin : taux horaire spécifique à une famille ─────────────────────────

    #[Route('/familles-mvc/{numFam}/tarif', name: 'famille_tarif_mvc')]
    public function tarifFamille(string $numFam, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $tarifsGE = $this->releveMensuelFamilleService->getTarifsFamille($numFam);

        return $this->render('admin/familles/tarif.html.twig', [
            'auth'    => true,
            'famille' => $famille,
            'tarifs'  => $tarifsGE,
            'tarifGEActif' => $this->releveMensuelFamilleService->getTarifActifFamille($numFam, 'GE'),
            'tarifMActif'  => $this->releveMensuelFamilleService->getTarifActifFamille($numFam, 'M'),
        ]);
    }

    #[Route('/familles-mvc/{numFam}/tarif/creer', name: 'famille_tarif_creer_mvc', methods: ['POST'])]
    public function creerTarifFamille(string $numFam, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('tarif_famille_creer', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('famille_tarif_mvc', ['numFam' => $numFam]);
        }

        $typePresta = $request->request->get('typePresta', 'GE');
        $taux       = (float)$request->request->get('tauxHoraire', 0);
        $dateDebut  = $request->request->get('dateDebut', date('Y-m'));

        try {
            $this->releveMensuelFamilleService->creerTarifFamille($numFam, $typePresta, $taux, $dateDebut);
            $this->addFlash('success', 'Taux horaire enregistré pour cette famille.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('famille_tarif_mvc', ['numFam' => $numFam]);
    }

    #[Route('/familles-mvc/{numFam}/toggle-km/{typePresta}', name: 'famille_toggle_km_mvc', methods: ['POST'])]
    public function toggleKmExoneration(string $numFam, string $typePresta, Request $request, TarifFamilleRepository $tarifFamilleRepo): JsonResponse
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }
        if (!$this->isCsrfTokenValid('ajax', $request->headers->get('X-CSRF-Token', ''))) {
            return $this->json(['error' => 'Token invalide'], 403);
        }

        $nouveau = $tarifFamilleRepo->toggleExonereKmType($numFam, $typePresta);
        return $this->json(['exonereKm' => $nouveau, 'type' => strtoupper($typePresta)]);
    }

    // ── Admin : exceptions de facturation d'une famille ───────────────────────

    #[Route('/familles-mvc/{numFam}/exception/ajouter', name: 'famille_exception_ajouter_mvc', methods: ['POST'])]
    public function ajouterException(string $numFam, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $moisAnnee  = $request->request->get('moisAnnee');
        $typePresta = $request->request->get('typePresta', 'GE');
        $libele     = $request->request->get('libele', '');
        $montant    = (float)$request->request->get('montant', 0);

        if (!$moisAnnee || !$libele) {
            $this->addFlash('error', 'Le mois et le libellé sont obligatoires.');
            return $this->redirectToRoute('admin_famille_detail_mvc', ['numFam' => $numFam]);
        }

        try {
            $this->releveMensuelFamilleService->ajouterOuModifierException(
                $numFam, $moisAnnee, $typePresta, $libele, $montant
            );
            $this->addFlash('success', 'Exception de facturation enregistrée.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_famille_detail_mvc', ['numFam' => $numFam]);
    }

    #[Route('/familles-mvc/{numFam}/exception/supprimer', name: 'famille_exception_supprimer_mvc', methods: ['POST'])]
    public function supprimerException(string $numFam, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $moisAnnee  = $request->request->get('moisAnnee');
        $typePresta = $request->request->get('typePresta', 'GE');

        if ($moisAnnee) {
            $this->releveMensuelFamilleService->supprimerException($numFam, $moisAnnee, $typePresta);
            $this->addFlash('success', 'Exception supprimée.');
        }

        return $this->redirectToRoute('admin_famille_detail_mvc', ['numFam' => $numFam]);
    }

    // ── Facture famille (espace famille) ─────────────────────────────────────

    #[Route('/famille/ma-facture', name: 'famille_ma_facture_mvc')]
    public function maFacture(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $numFam  = $this->authService->famille_id();
        $famille = $this->authService->getFamille();

        if (!$numFam) {
            return $this->redirectToRoute('app_login');
        }

        $moisAnnee  = $request->query->get('mois', date('Y-m'));
        $typeFilter = strtoupper(trim($request->query->get('type', ''))) ?: null;
        if ($typeFilter && !in_array($typeFilter, ['GE', 'MENA'], true)) {
            $typeFilter = null;
        }

        $facture = $this->factureService->calculerFacture($numFam, $moisAnnee);
        $releves = $this->releveFamilleBuilder->buildReleves($numFam, $famille, $moisAnnee, $facture, $typeFilter);

        return $this->render('familles/facture.html.twig', [
            'releves'        => $releves,
            'moisAnnee'      => $moisAnnee,
            'typeFilter'     => $typeFilter,
            'numFam'         => $numFam,
            'facture'        => $facture,
            'vacancesConfig' => $this->vacancesRepo->findActif(),
        ]);
    }

    #[Route('/famille/ma-facture/sauvegarder', name: 'famille_ma_facture_sauvegarder_mvc', methods: ['POST'])]
    public function sauvegarderFacture(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $numFam = $this->authService->famille_id();

        if (!$numFam) {
            return $this->redirectToRoute('app_login');
        }

        $moisAnnee = $request->request->get('moisAnnee', date('Y-m'));
        $facture   = $this->factureService->calculerFacture($numFam, $moisAnnee);
        $prestTypes = [];
        if (!empty($facture['prestationsGE']))   $prestTypes[] = 'ENFA';
        if (!empty($facture['prestationsMENA'])) $prestTypes[] = 'MENA';
        if (empty($prestTypes)) $prestTypes[] = 'ENFA';

        $this->releveMensuelFamilleService->sauvegarderQuestionnaire($numFam, $moisAnnee, $prestTypes, [
            'avisPonctualite'  => $request->request->get('avisPonctualite'),
            'avisReguRela'     => $request->request->get('avisReguRela'),
            'avisRespectHo'    => $request->request->get('avisRespectHo'),
            'avisQualiteTr'    => $request->request->get('avisQualiteTr'),
            'typeReglement'    => $request->request->get('typeReglement'),
            'numCheque'        => $request->request->get('numCheque'),
            'nbrCESU'          => $request->request->get('nbrCESU'),
            'montantPrincipal' => $request->request->get('montantPrincipal'),
            'complementCESU'   => $request->request->get('complementCESU'),
            'montantComplement' => $request->request->get('montantComplement'),
            'signer'           => $request->request->get('signer'),
        ]);

        $this->addFlash('success', 'Questionnaire enregistré avec succès.');

        return $this->redirectToRoute('famille_ma_facture_mvc', ['mois' => $moisAnnee]);
    }

    #[Route('/famille/ma-facture/imprimer', name: 'famille_ma_facture_print_mvc')]
    public function maFacturePrint(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $numFam  = $this->authService->famille_id();
        $famille = $this->authService->getFamille();

        if (!$numFam) {
            return $this->redirectToRoute('app_login');
        }

        $moisAnnee = $request->query->get('mois', date('Y-m'));
        $facture   = $this->factureService->calculerFacture($numFam, $moisAnnee);

        return $this->render('admin/factures/print.html.twig', [
            'famille'   => $famille,
            'facture'   => $facture,
            'moisAnnee' => $moisAnnee,
            'backUrl'   => $this->generateUrl('famille_ma_facture_mvc', ['mois' => $moisAnnee]),
        ]);
    }
}
