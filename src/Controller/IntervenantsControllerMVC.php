<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\FamilleIntervenantService;
use App\Service\FamilleService;
use App\Service\HoraireinterService;
use App\Service\IntervenantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    ) {}

    // ── Admin : liste ─────────────────────────────────────────────────────────

    #[Route('/intervenants-mvc', name: 'intervenants_mvc')]
    public function intervenants(Request $request): Response
    {
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
        $id   = $this->resolveId($id);
        $user = $this->intervenantService->getInfosIntervenant($id);
        if (!$user) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        $prestations = $this->horaireService->getPrestationsParIntervenant($id);

        return $this->render('intervenants/hours/list.html.twig', [
            'auth'        => $this->authService->check(),
            'user'        => $user,
            'prestations' => $prestations,
            'isAdmin'     => $this->authService->isAdmin(),
        ]);
    }

    // ── Saisie / suivi des heures ─────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/suivie', name: 'intervenant_suivie_mvc')]
    public function suivie(int $id, Request $request): Response
    {
        $id   = $this->resolveId($id);
        $user = $this->intervenantService->getInfosIntervenant($id);
        if (!$user) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        $familles = $this->familleIntervenantService->getFamillesForIntervenant($id);

        return $this->render('intervenants/hours/followup.html.twig', [
            'auth'          => $this->authService->check(),
            'user'          => $user,
            'isAdmin'       => $this->authService->isAdmin(),
            'familles'      => $familles,
            'nbrJourSaisie' => $this->horaireService->getNbrJourSaisie(),
            'editId'        => $request->query->get('edite'),
        ]);
    }

    // ── Planning hebdomadaire ─────────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/planning', name: 'intervenant_planning_mvc')]
    public function planning(int $id, Request $request): Response
    {
        $id   = $this->resolveId($id);
        $user = $this->intervenantService->getInfosIntervenant($id);
        if (!$user) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        $planning = $this->familleIntervenantService->getPlanningHebdo($id);
        $familles = $this->familleIntervenantService->getFamillesForIntervenant($id);

        return $this->render('intervenants/planning.html.twig', [
            'auth'     => $this->authService->check(),
            'user'     => $user,
            'planning' => $planning,
            'familles' => $familles,
        ]);
    }

    // ── Scanner QR ────────────────────────────────────────────────────────────

    #[Route('/intervenants-mvc/{id}/qr-scan', name: 'intervenant_qr_mvc')]
    public function qrScan(int $id, Request $request): Response
    {
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
            return $this->json(['success' => true, 'action' => 'saved']);
        }

        return $this->json(['success' => true, 'action' => 'debut_recorded']);
    }

    // ── Admin : archiver ──────────────────────────────────────────────────────

    #[Route('/intervenants-mvc/archiver/{id}', name: 'intervenant_archiver_mvc', methods: ['POST'])]
    public function archiver(int $id, Request $request): Response
    {
        if (!$this->authService->isAdmin()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

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
        $terme = $request->query->get('q', '');
        $users = !empty($terme) ? $this->intervenantService->rechercherIntervenants($terme) : [];

        return $this->render('intervenants/recherche.html.twig', [
            'auth'  => $this->authService->check(),
            'users' => $users,
            'terme' => $terme,
        ]);
    }

    // ── Utilitaire ────────────────────────────────────────────────────────────

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
