<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\FamilleIntervenantService;
use App\Service\FamilleService;
use App\Service\HoraireinterService;
use App\Service\IntervenantService;
use App\Twig\FamilleExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IntervenantsControllerMVC extends AbstractController
{
    public function __construct(
        private IntervenantService        $intervenantService,
        private AuthService               $authService,
        private HoraireinterService       $horaireService,
        private FamilleService            $familleService,
        private FamilleIntervenantService $familleIntervenantService,
        private FamilleExtension          $familleExtension,
    ) {}

    // ── Admin : liste ─────────────────────────────────────────────────────────

    #[Route('/intervenants-mvc', name: 'intervenants_mvc')]
    public function intervenants(Request $request): Response
    {
        if ($redirect = $this->guardIntervenantAccess()) {
            return $redirect;
        }

        if (!$this->authService->isAdmin()) {
            return $this->redirectToRoute('intervenant_panel_mvc', [
                'id' => $this->authService->intervenant_id()
            ]);
        }

        $users = $this->intervenantService->getTousLesIntervenants();

        return $this->render('admin/intervenants/list.html.twig', [
            'auth'  => $this->authService->check(),
            'users' => $users,
        ]);
    }

    // ── Dashboard intervenant ─────────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/panel', name: 'intervenant_panel_mvc')]
    public function intervenantPanel(int $id, Request $request): Response
    {
        if ($redirect = $this->guardIntervenantAccess()) {
            return $redirect;
        }

        $id = $this->resolveId($id);

        $user = $this->intervenantService->getInfosIntervenant($id);
        if (!$user) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        $prochainCreneau = $this->familleIntervenantService->getProchainCreneau($id);
        $familles        = $this->familleIntervenantService->getFamillesForIntervenant($id);
        $stats           = $this->horaireService->getDashboardStats($id);
        $alertes         = $this->horaireService->getRelevesASigner($id);
        $dernieres       = array_slice(
            $this->horaireService->getPrestationsParIntervenant($id),
            0, 5
        );

        return $this->render('intervenants/dashboard.html.twig', [
            'auth'            => $this->authService->check(),
            'user'            => $user,
            'stats'           => $stats,
            'alertes'         => $alertes,
            'dernieres'       => $dernieres,
            'prochainCreneau' => $prochainCreneau,
            'familles'        => $familles,
        ]);
    }

    // ── Profil ────────────────────────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/profile', name: 'intervenant_profile_mvc')]
    public function intervenantProfile(int $id, Request $request): Response
    {
        if ($redirect = $this->guardIntervenantAccess()) {
            return $redirect;
        }

        $id   = $this->resolveId($id);
        $user = $this->intervenantService->getInfosIntervenant($id);
        if (!$user) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        return $this->render('intervenants/profile.html.twig', [
            'auth' => $this->authService->check(),
            'user' => $user,
        ]);
    }

    // ── Heures saisies ────────────────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/heures', name: 'intervenant_heures_mvc')]
    public function heures(int $id, Request $request): Response
    {
        if ($redirect = $this->guardIntervenantAccess()) {
            return $redirect;
        }

        $id   = $this->resolveId($id);
        $user = $this->intervenantService->getInfosIntervenant($id);
        if (!$user) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        $prestations = $this->horaireService->getPrestationsParIntervenant($id);

        $nbrJours   = $this->horaireService->getNbrJourSaisie();
        $dateLimite = (new \DateTime('today'))->modify('-' . $nbrJours . ' days');

        // Regroupement par mois (les prestations sont déjà triées par date DESC).
        $moisFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];
        $groupesMois = [];
        foreach ($prestations as $p) {
            $date = $p->getDatePresta();
            $key  = $date->format('Y-m');
            if (!isset($groupesMois[$key])) {
                $groupesMois[$key] = [
                    'key'         => $key,
                    'label'       => $moisFr[(int)$date->format('n')] . ' ' . $date->format('Y'),
                    'prestations' => [],
                    'totalHeures' => 0.0,
                    'nb'          => 0,
                ];
            }
            $deb = $p->getHeureDebutPresta();
            $fin = $p->getHeureFinPresta();
            if ($deb && $fin) {
                $h = ($fin->getTimestamp() - $deb->getTimestamp()) / 3600;
                if ($h > 0) {
                    $groupesMois[$key]['totalHeures'] += $h;
                }
            }
            $groupesMois[$key]['prestations'][] = $p;
            $groupesMois[$key]['nb']++;
        }

        // Le mois en cours doit toujours apparaître, même sans aucune prestation.
        $moisActuelKey = (new \DateTime('today'))->format('Y-m');
        if (!isset($groupesMois[$moisActuelKey])) {
            $now = new \DateTime('today');
            $groupesMois[$moisActuelKey] = [
                'key'         => $moisActuelKey,
                'label'       => $moisFr[(int)$now->format('n')] . ' ' . $now->format('Y'),
                'prestations' => [],
                'totalHeures' => 0.0,
                'nb'          => 0,
            ];
            // Réordonner par mois décroissant (le mois courant remonte en tête).
            krsort($groupesMois);
        }

        // Services proposés par le planning (PREST) de l'intervenant.
        $servicesPlanning = [];
        foreach ($this->familleIntervenantService->getTypesParFamille($id) as $types) {
            foreach ($types as $t) {
                $servicesPlanning[strtoupper($t)] = true;
            }
        }
        
        // Services présents dans les heures réellement déclarées (inclut l'occasionnel).
        $servicesDeclares = [];
        foreach ($prestations as $p) {
            $servicesDeclares[strtoupper((string) $p->getTypePresta())] = true;
        }
        // Services à proposer = planning ∪ déclarés, dans l'ordre MENA puis ENFA.
        $servicesDispo = [];
        foreach (['MENA', 'ENFA'] as $t) {
            if (isset($servicesPlanning[$t]) || isset($servicesDeclares[$t])) {
                $servicesDispo[] = $t;
            }
        }
        // Service par défaut : celui du planning en priorité, sinon le premier disponible.
        $serviceDefaut = array_key_first($servicesPlanning) ?: ($servicesDispo[0] ?? 'MENA');

        return $this->render('intervenants/hours/list.html.twig', [
            'auth'          => $this->authService->check(),
            'user'          => $user,
            'prestations'   => $prestations,
            'groupesMois'   => array_values($groupesMois),
            'moisActuel'    => (new \DateTime('today'))->format('Y-m'),
            'servicesDispo' => $servicesDispo,
            'serviceDefaut' => $serviceDefaut,
            'isAdmin'       => $this->authService->isAdmin(),
            'dateLimite'    => $dateLimite,
        ]);
    }

    // ── Saisie / suivi des heures ─────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/suivie', name: 'intervenant_suivie_mvc')]
    public function suivie(int $id, Request $request): Response
    {
        if ($redirect = $this->guardIntervenantAccess()) {
            return $redirect;
        }

        $id   = $this->resolveId($id);
        $user = $this->intervenantService->getInfosIntervenant($id);
        if (!$user) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        $familles     = $this->familleIntervenantService->getFamillesForIntervenant($id);
        $assignations = $this->familleIntervenantService->getAssignationsActives($id);
        // Types (MENA/ENFA) par famille — tous les proposers PREST, pour filtrer le select
        $typesParFamille = $this->familleIntervenantService->getTypesParFamille($id);

        // Plage de facturation courante : 25 du mois précédent → aujourd'hui
        $today = new \DateTime('today');
        $day   = (int)$today->format('d');
        if ($day >= 25) {
            $billingStart = new \DateTime($today->format('Y-m') . '-25');
        } else {
            $billingStart = (clone $today)->modify('first day of last month')->modify('+24 days');
        }

        return $this->render('intervenants/hours/followup.html.twig', [
            'auth'              => $this->authService->check(),
            'user'              => $user,
            'intervenantId'     => $id,
            'isAdmin'           => $this->authService->isAdmin(),
            'familles'          => $familles,
            'assignations'      => $assignations,
            'typesParFamille'   => $typesParFamille,
            'periodeDebut'      => $billingStart,
            'editId'            => $request->query->get('edite'),
            'toutesLesFamilles' => $this->familleService->getToutesLesFamilles(),
        ]);
    }

    // ── Planning mensuel ──────────────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/planning', name: 'intervenant_planning_mvc')]
    public function planning(int $id, Request $request): Response
    {
        if ($redirect = $this->guardIntervenantAccess()) {
            return $redirect;
        }

        $id   = $this->resolveId($id);
        $user = $this->intervenantService->getInfosIntervenant($id);
        if (!$user) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        $now       = new \DateTime();
        $moisParam = $request->query->get('mois');
        if ($moisParam && preg_match('/^\d{4}-\d{2}$/', $moisParam)) {
            [$year, $month] = array_map('intval', explode('-', $moisParam));
        } else {
            // Période de facturation courante : si on est après le 24, la nouvelle période
            // a commencé le 25 du mois courant, donc le mois de référence est le mois suivant.
            $nowDay = (int)$now->format('d');
            if ($nowDay >= 25) {
                $billingRef = (clone $now)->modify('+1 month');
            } else {
                $billingRef = clone $now;
            }
            $year  = (int)$billingRef->format('Y');
            $month = (int)$billingRef->format('m');
        }

        // Plage de facturation : 25 du mois M-1 → 24 du mois M
        $periodStart = (new \DateTime(sprintf('%04d-%02d-01', $year, $month)))
            ->modify('-1 month')
            ->modify('+24 days'); // = 25 du mois précédent
        $periodEnd = new \DateTime(sprintf('%04d-%02d-24', $year, $month));

        // Grille : du lundi de la semaine contenant periodStart
        //          au dimanche de la semaine contenant periodEnd
        $startDow  = (int)$periodStart->format('N'); // 1=Lun … 7=Dim
        $gridStart = (clone $periodStart)->modify('-' . ($startDow - 1) . ' days');
        $endDow    = (int)$periodEnd->format('N');
        $gridEnd   = (clone $periodEnd)->modify('+' . (7 - $endDow) . ' days');
        $nbGridDays = ((int)$gridStart->diff($gridEnd)->days) + 1;

        $moisOffset  = sprintf('%04d-%02d', $year, $month);
        $moisPrev    = (new \DateTime(sprintf('%04d-%02d-01', $year, $month)))->modify('-1 month')->format('Y-m');
        $moisNext    = (new \DateTime(sprintf('%04d-%02d-01', $year, $month)))->modify('+1 month')->format('Y-m');

        // Période de facturation "courante" pour le badge "Période courante"
        $nowDay2 = (int)$now->format('d');
        $nowRef  = $nowDay2 >= 25 ? (clone $now)->modify('+1 month') : clone $now;
        $moisCourant = $nowRef->format('Y-m');

        $planning = $this->familleIntervenantService->getPlanningMensuel($periodStart, $periodEnd, $id);
        $familles = $this->familleIntervenantService->getFamillesForIntervenant($id);

        return $this->render('intervenants/planning.html.twig', [
            'auth'         => $this->authService->check(),
            'user'         => $user,
            'planning'     => $planning,
            'familles'     => $familles,
            'year'         => $year,
            'month'        => $month,
            'periodStart'  => $periodStart,
            'periodEnd'    => $periodEnd,
            'gridStart'    => $gridStart,
            'nbGridDays'   => $nbGridDays,
            'moisOffset'   => $moisOffset,
            'moisPrev'     => $moisPrev,
            'moisNext'     => $moisNext,
            'moisCourant'  => $moisCourant,
        ]);
    }

    // ── Scanner QR ────────────────────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/qr-scan', name: 'intervenant_qr_mvc')]
    public function qrScan(int $id, Request $request): Response
    {
        if ($redirect = $this->guardIntervenantAccess()) {
            return $redirect;
        }

        $id   = $this->resolveId($id);
        $user = $this->intervenantService->getInfosIntervenant($id);
        if (!$user) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        $familles     = $this->familleIntervenantService->getFamillesForIntervenant($id);
        $assignations = $this->familleIntervenantService->getAssignationsActives($id);

        return $this->render('intervenants/qr-scan.html.twig', [
            'auth'        => $this->authService->check(),
            'user'        => $user,
            'familles'    => $familles,
            'assignations'=> $assignations,
        ]);
    }

    // ── API QR (AJAX POST) ────────────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/qr-api', name: 'intervenant_qr_api_mvc', methods: ['POST'])]
    public function qrApi(int $id, Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        if (!$this->authService->isAdmin() && !$this->authService->isIntervenantAccepted()) {
            return $this->json(['success' => false, 'error' => 'Candidature en attente d\'acceptation'], 403);
        }

        $id = $this->resolveId($id);

        // Vérification autorisation
        if (!$this->authService->isAdmin() && $id !== $this->authService->intervenant_id()) {
            return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $data       = json_decode($request->getContent(), true) ?? [];
        $action     = $data['action']     ?? null;
        $numFam     = $data['numFam']     ?? null;
        $nomFam     = $data['nomFam']     ?? '';
        $heureDebut = $data['heureDebut'] ?? null;
        $heureFin   = $data['heureFin']   ?? null;
        $date       = $data['date']       ?? date('Y-m-d');
        $type       = $data['type']       ?? 'ENFA';
        $km         = $data['km']         ?? null;

        if (!$numFam) {
            return $this->json(['success' => false, 'error' => 'Famille manquante'], 400);
        }

        if (!$this->familleIntervenantService->peutPointer($id, $numFam)) {
            return $this->json(['success' => false, 'error' => 'Intervenant non assigné à cette famille'], 403);
        }

        if ($action === 'fin' && $heureDebut && $heureFin) {
            try {
                $this->horaireService->ajouterPrestation([
                    'numFam'           => $numFam,
                    'nomFam'           => $nomFam,
                    'numInter'         => $id,
                    'datePresta'       => $date,
                    'heureDebutPresta' => $heureDebut,
                    'heureFinPresta'   => $heureFin,
                    'typePresta'       => $type,
                    'kmAvecEnfant'     => $km,
                ]);
            } catch (\Throwable $e) {
                return $this->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return $this->json(['success' => true, 'action' => 'saved']);
        }

        return $this->json(['success' => true, 'action' => 'debut_recorded']);
    }

    // ── Déclaration via QR famille (scan appareil photo natif) ─────────────────
    // Le QR de la famille encode l'URL absolue de cette route. L'intervenant scanne
    // avec l'appareil photo de son téléphone → la page s'ouvre → on le redirige vers
    // son formulaire de saisie pré-rempli avec la famille (pas besoin de la caméra
    // de l'app, donc pas de contrainte HTTPS pour getUserMedia).
    #[Route('/declarer/{numFam}', name: 'declarer_qr_mvc', methods: ['GET'])]
    public function declarerViaQr(string $numFam, Request $request): Response
    {
        // Non connecté → on mémorise l'URL demandée (TargetPath standard Symfony)
        // pour y revenir automatiquement après le login.
        if (!$this->authService->check()) {
            $request->getSession()->set('_security.main.target_path', $request->getUri());
            return $this->redirectToRoute('app_login');
        }

        $interId = $this->authService->intervenant_id();

        // Un admin n'a pas de saisie personnelle : on l'informe simplement
        if (!$interId) {
            $this->addFlash('error', "Ce QR est destiné aux intervenants. Connectez-vous avec un compte intervenant.");
            return $this->redirectToRoute('intervenants_mvc');
        }

        $today   = (new \DateTime())->format('Y-m-d');
        $famille = $this->familleService->getFamilleParNumero($numFam);

        // Intervenant assigné à cette famille → saisie sur la vraie famille
        if ($famille && $this->familleIntervenantService->peutPointer((int) $interId, (string) $numFam)) {
            return $this->redirectToRoute('intervenant_suivie_mvc', [
                'id'      => $interId,
                'famille' => $numFam,
                'date'    => $today,
            ]);
        }

        // Non assigné (ou famille hors de sa liste) → saisie OCCASIONNELLE
        // avec le VRAI nom de famille pré-rempli (via famille_label : parent/nom).
        $nom = $this->familleExtension->familleLabel($famille ?? $numFam) ?: $numFam;
        return $this->redirectToRoute('intervenant_suivie_mvc', [
            'id'      => $interId,
            'famille' => '0',
            'nom'     => $nom,
            'date'    => $today,
        ]);
    }

    // ── Admin : archiver ──────────────────────────────────────────────────────

    #[Route('/intervenants-mvc/archiver/{id}', name: 'intervenant_archiver_mvc', methods: ['POST'])]
    public function archiver(int $id, Request $request): Response
    {
        if (!$this->authService->isAdmin()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }
        $id = $this->resolveId($id);
        $success = $this->intervenantService->archiverIntervenant($id);

        if ($success) {
            $this->addFlash('success', 'Intervenant archivé avec succès');
        } else {
            $this->addFlash('error', 'Impossible d\'archiver cet intervenant');
        }

        return $this->redirectToRoute('intervenants_mvc');
    }

    // ── Admin : recherche ─────────────────────────────────────────────────────

    #[Route('/intervenants-mvc/recherche', name: 'intervenant_recherche_mvc')]
    public function recherche(Request $request): Response
    {
        if (!$this->authService->isAdmin()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $terme = $request->query->get('q', '');
        $users = !empty($terme) ? $this->intervenantService->rechercherIntervenants($terme) : [];

        return $this->render('intervenants/recherche.html.twig', [
            'auth'  => $this->authService->check(),
            'users' => $users,
            'terme' => $terme,
        ]);
    }

    // ── Utilitaires ───────────────────────────────────────────────────────────

    /**
     * Gate for every intervenant action.
     * - Unauthenticated → redirect to login.
     * - Non-admin whose linked candidature is still 'En attente' → redirect to
     *   login with an explanatory flash so the user understands why access is blocked.
     * Returns null when access is allowed.
     */
    private function guardIntervenantAccess(): ?RedirectResponse
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->authService->isAdmin()) {
            return null;
        }

        if (!$this->authService->isIntervenantAccepted()) {
            $this->addFlash('warning', 'Votre candidature est en cours de traitement. Votre accès sera activé une fois votre candidature acceptée.');
            return $this->redirectToRoute('app_login');
        }

        if (!$this->authService->isIntervenantActif()) {
            $this->addFlash('warning', 'Votre compte a été archivé. Veuillez contacter l\'administration.');
            return $this->redirectToRoute('app_login');
        }

        return null;
    }

    /**
     * Pour un non-admin, force l'ID de l'intervenant connecté.
     * Pour un admin, conserve l'ID passé en URL.
     */
    private function resolveId(int $id): int
    {
        if ($this->authService->isAdmin()) {
            return $id;
        }

        return $this->authService->intervenant_id() ?? $id;
    }
}
