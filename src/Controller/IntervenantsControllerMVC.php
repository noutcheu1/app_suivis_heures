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
        private \App\Repository\VacancesReponseIntervenantRepository $reponseInterRepo,
        private \App\Repository\VacancesConfigRepository $vacancesRepo,
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

    #[Route('/intervenants-mvc/{id}/pointer', name: 'intervenant_pointer_mvc')]
    public function pointer(int $id): Response
    {
        if ($redirect = $this->guardIntervenantAccess()) {
            return $redirect;
        }
        $id = $this->resolveId($id);

        return $this->render('intervenants/pointer.html.twig', [
            'auth' => $this->authService->check(),
            'user' => $this->intervenantService->getInfosIntervenant($id),
            // Pointages NON terminés → bouton « Clôturer » (sans QR, l'intervenant est connecté).
            'pointagesEnCours' => $this->horaireService->getPointagesEnCours($id),
        ]);
    }

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
            // Pointages NON terminés → récupération d'oubli (clôture sans QR).
            'pointagesEnCours'=> $this->horaireService->getPointagesEnCours($id),
            // Résumé de la dernière réponse à une campagne de congés.
            'reponseVacances' => ($rv = $this->reponseInterRepo->findLatestByNumInter($id)),
            'campagneVacances'=> $rv ? $this->vacancesRepo->find($rv->getVacancesConfigId()) : null,
        ]);
    }

    // ── Clôturer un pointage oublié (sans QR, intervenant connecté) ──────────
    #[Route('/intervenants-mvc/{id}/cloturer-pointage', name: 'intervenant_cloturer_pointage_mvc', methods: ['POST'])]
    public function cloturerPointage(int $id, Request $request): Response
    {
        if ($redirect = $this->guardIntervenantAccess()) {
            return $redirect;
        }
        $id = $this->resolveId($id);

        if (!$this->isCsrfTokenValid('cloturer', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide. Réessayez.');
            return $this->redirectToRoute('intervenant_panel_mvc', ['id' => $id]);
        }

        try {
            $this->horaireService->cloturerPointageOublie(
                $id,
                (int) $request->request->get('horaireId'),
                (string) $request->request->get('heure', ''),
            );
            $this->addFlash('success', 'Pointage clôturé.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('intervenant_panel_mvc', ['id' => $id]);
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

    // ── Page d'attente après clic « Signer » (génère le PDF + envoie l'email) ──
    #[Route('/intervenants-mvc/{id}/releve-signe', name: 'intervenant_releve_signe_mvc')]
    public function releveSigne(int $id, Request $request): Response
    {
        if ($redirect = $this->guardIntervenantAccess()) {
            return $redirect;
        }
        $id = $this->resolveId($id);

        return $this->render('intervenants/hours/releve-signe.html.twig', [
            'auth'    => $this->authService->check(),
            'id'      => $id,
            'type'    => strtoupper($request->query->get('type', 'ENFA')),
            'periode' => $request->query->get('periode', ''),
            'mois'    => (int) $request->query->get('mois', 0),
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
        // Types (MENA/ENFA) par famille tous les proposers PREST, pour filtrer le select
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

        $assignations = $this->familleIntervenantService->getAssignationsActives($id);

        // Familles reconnues au scan = TOUTES celles pour lesquelles l'intervenant
        // a un planning PREST (avec le(s) type(s)). Sinon le QR bascule en occasionnel.
        $famillesScan = [];
        foreach ($this->familleIntervenantService->getTypesParFamille($id) as $numFam => $types) {
            $famillesScan[] = [
                'numFam' => (string) $numFam,
                'mena'   => in_array('MENA', $types, true),
                'enfa'   => in_array('ENFA', $types, true),
            ];
        }

        // Familles DÉJÀ pointées aujourd'hui (règle : 1 créneau/famille/jour) → on
        // l'indique dès le scan au lieu de laisser démarrer un compteur qui échouera.
        $dejaPointe = [];
        $prestasJour = $this->horaireService->getPrestationsParIntervenant(
            $id,
            new \DateTime('today 00:00:00'),
            new \DateTime('today 23:59:59'),
        );
        foreach ($prestasJour as $p) {
            if ($p->getNumFam()) {
                $dejaPointe[(string) $p->getNumFam()] = true;
            }
        }

        return $this->render('intervenants/qr-scan.html.twig', [
            'auth'         => $this->authService->check(),
            'user'         => $user,
            'famillesScan' => $famillesScan,
            'assignations' => $assignations,
            'dejaPointe'   => array_keys($dejaPointe),
            // Pointages en cours (côté serveur) → afficher « Terminer » au scan + oublis.
            'enCours'      => $this->horaireService->getPointagesEnCours($id),
        ]);
    }

    // ── API QR (AJAX POST) ────────────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/qr-api', name: 'intervenant_qr_api_mvc', methods: ['POST'])]
    public function qrApi(int $id, Request $request): JsonResponse
    {
        if (!$this->authService->check()) {
            return $this->json(['success' => false, 'error' => 'Non authentifié'], 401);
        }

        if (!$this->isCsrfTokenValid('ajax', $request->headers->get('X-CSRF-Token', ''))) {
            return $this->json([
                'success' => false,
                'error'   => 'Votre session a expiré. Rechargez la page puis réessayez.',
            ], 403);
        }

        if (!$this->authService->isAdmin() && !$this->authService->isIntervenantAccepted()) {
            return $this->json(['success' => false, 'error' => 'Candidature en attente d\'acceptation'], 403);
        }

        $id = $this->resolveId($id);

        // Vérification autorisation
        if (!$this->authService->isAdmin() && $id !== $this->authService->intervenant_id()) {
            return $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        $data    = json_decode($request->getContent(), true) ?? [];
        $action  = $data['action'] ?? null;
        $numFam  = $data['numFam'] ?? null;
        $type    = $data['type']   ?? 'ENFA';
        $km      = isset($data['km']) && $data['km'] !== null && $data['km'] !== '' ? (float) $data['km'] : null;
        // Heures du téléphone : arrondie (relevé) + réelle (trace). Format HH:MM validé.
        $heure       = $this->heureValideQr($data['heure'] ?? null);
        $heureReelle = $this->heureValideQr($data['heureReelle'] ?? null);

        if (!$numFam) {
            return $this->json(['success' => false, 'error' => 'Famille manquante'], 400);
        }

        // Nom réel résolu côté serveur (vraie famille même hors planning → occasionnel).
        $famille = $this->familleService->getFamilleParNumero((string) $numFam);
        $nomFam  = $this->familleExtension->familleLabel($famille ?? $numFam) ?: (string) $numFam;

        // Famille connue = présente en base ET non archivée.
        $familleConnue = $famille !== null && $famille->getArchive() !== true;

        // Vérification (avant de démarrer) : la famille est-elle reconnue ?
        if ($action === 'verifier') {
            return $this->json([
                'success' => true,
                'connue'  => $familleConnue,
                'nom'     => $familleConnue ? $nomFam : null,
            ]);
        }

        // Famille inconnue (absente/archivée) → on n'enregistre pas : saisie manuelle requise.
        if (!$familleConnue) {
            return $this->json([
                'success' => false,
                'inconnue' => true,
                'error'   => 'Famille inconnue utilisez la saisie manuelle.',
            ], 404);
        }

        try {
            if ($action === 'debut') {
                $this->horaireService->demarrerPointage($id, (string) $numFam, $nomFam, (string) $type, $heure, $heureReelle);
                return $this->json(['success' => true, 'action' => 'debut']);
            }

            if ($action === 'fin') {
                $h = $this->horaireService->terminerPointage($id, (string) $numFam, $km, $heure, $heureReelle);
                return $this->json([
                    'success' => true,
                    'action'  => 'fin',
                    'debut'   => $h->getHeureDebutPresta()?->format('H:i'),
                    'fin'     => $h->getHeureFinPresta()?->format('H:i'),
                ]);
            }
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return $this->json(['success' => false, 'error' => 'Action inconnue'], 400);
    }

    /** Valide une heure « HH:MM » envoyée par le téléphone (null si invalide). */
    private function heureValideQr(mixed $heure): ?string
    {
        return is_string($heure) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $heure) ? $heure : null;
    }

    // ── Déclaration via QR famille (scan appareil photo natif) ─────────────────
    // Le QR de la famille encode l'URL absolue de cette route. L'intervenant scanne
    // avec l'appareil photo de son téléphone → la page s'ouvre → on le redirige vers
    // son formulaire de saisie pré-rempli avec la famille (pas besoin de la caméra
    // de l'app, donc pas de contrainte HTTPS pour getUserMedia).
    #[Route('/declarer/{token}', name: 'declarer_qr_mvc', methods: ['GET'])]
    public function declarerViaQr(string $token): Response
    {
        // Ancienne entrée QR (compat) : on redirige vers le flux pointage basé sur le
        // JETON. Le QR pointe désormais directement sur /pointage/{token}.
        return $this->redirectToRoute('pointage_saisie', ['token' => $token]);
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
