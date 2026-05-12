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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;


final class DashboardController extends AbstractController
{
    public function __construct(
        private RouterInterface $router,
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

        $stats = [];
        $user = [];

        if ($this->authService->isAdmin()) {
            $stats = [
                'total_intervenants' => $this->intervenantService->countIntervenants(),
                'total_familles' => $this->familleService->countFamilles(),
                'heures_ce_mois' => $this->horaireService->countHeuresMois(date('m/Y')),
            ];
            $user = ['nomCompletInter' => 'Admin', 'role' => 'ADMIN'];
        } elseif ($this->authService->isIntervenant()) {
            $intervenant = $this->authService->getIntervenant();
            $user = [
                'nomCompletInter' => $intervenant ? $intervenant->getNomCompletInter() : 'Intervenant',
                'id' => $this->authService->intervenant_id(),
                'role' => 'INTERVENANT',
            ];
            $stats = [
                'heures_mois' => $this->horaireService->countHeuresParIntervenant(
                    $this->authService->intervenant_id(),
                    date('m/Y')
                ),
                'heures_total' => $this->horaireService->countHeuresParIntervenant(
                    $this->authService->intervenant_id()
                ),
            ];
            return new RedirectResponse(
                $this->router->generate('intervenant_panel_mvc', [
                    'id' => $this->authService->getIntervenant()->getId(), // ou méthode équivalente
                ])
            );
        } elseif ($this->authService->isFamille()) {
            $famille = $this->authService->getFamille();
            $user = [
                'nomFamille' => $famille ? $famille->getNomFamille() : 'Famille',
                'id' => $this->authService->famille_id(),
                'role' => 'FAMILLE',
            ];
            $stats = [
                'heures_mois' => $this->horaireService->countHeuresMoisParFamille(
                    ($this->authService->famille_id()),
                    date('m/Y')
                ),
                'montant_du' => $this->familleService->calculerMontantDu(
                    $this->authService->famille_id(),
                    date('m/Y')
                ),
            ];
            return new RedirectResponse($this->router->generate('famille_panel_mvc'));
        }
        
        return $this->render('dashboard/index.html.twig', [
            'auth' => true,
            'authService' => $this->authService,
            'stats' => $stats,
            'user' => $user,
        ]);
    }
}
