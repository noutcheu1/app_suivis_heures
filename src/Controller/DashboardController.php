<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\IntervenantService;
use App\Service\FamilleService;
use App\Service\HoraireinterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private IntervenantService $intervenantService,
        private FamilleService $familleService,
        private HoraireinterService $horaireService
    ) {}

    #[Route('/', name: 'dashboard')]
    public function dashboard(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        // Afficher le dashboard pour tous les utilisateurs authentifiés
        $stats = [];
        $user = [];

        if ($this->authService->isAdmin()) {
            $stats = [
                'total_intervenants' => $this->intervenantService->countIntervenants(),
                'total_familles' => $this->familleService->countFamilles(),
                'heures_ce_mois' => $this->horaireService->countHeuresMois(date('m/Y')),
            ];
            $user = ['nomCompletInter' => 'Admin'];
        } elseif ($this->authService->isIntervenant()) {
            $intervenant = $this->authService->getIntervenant();
            $user = [
                'nomCompletInter' => $intervenant ? $intervenant->getNomCompletInter() : 'Intervenant',
                'id' => $this->authService->intervenant_id()
            ];
        } elseif ($this->authService->isFamille()) {
            // TODO: Récupérer les infos de la famille depuis la session
            $user = ['Nom de la famille' => 'Famille'];
        }

        return $this->render('dashboard.html.twig', [
            'auth' => $this->authService->check(),
            'authService' => $this->authService,
            'stats' => $stats,
            'user' => $user
        ]);
    }
}
