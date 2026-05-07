<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\FamilleService;
use App\Service\HoraireinterService;
use App\Service\IntervenantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IntervenantsControllerMVC extends AbstractController
{
    public function __construct(
        private IntervenantService $intervenantService,
        private AuthService $authService,
        private HoraireinterService $horaireService,
        private FamilleService $familleService
    ) {}

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
            'auth' => $this->authService->check(),
            'users' => $users,
        ]);
    }

    #[Route('/intervenants-mvc/{id}/panel', name: 'intervenant_panel_mvc')]
    public function intervenantPanel(int $id, Request $request): Response
    {
        if (!$this->authService->isAdmin() && $id != $this->authService->intervenant_id()) {
            return $this->redirectToRoute('intervenant_panel_mvc', [
                'id' => $this->authService->intervenant_id()
            ]);
        }

        $user = $this->intervenantService->getInfosIntervenant($id);

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        return $this->render('intervenants/dashboard.html.twig', [
            'auth' => $this->authService->check(),
            'user' => $user,
        ]);
    }

    #[Route('/intervenants-mvc/{id}/profile', name: 'intervenant_profile_mvc')]
    public function intervenantProfile(int $id, Request $request): Response
    {
        if (!$this->authService->isAdmin() && $id != $this->authService->intervenant_id()) {
            return $this->redirectToRoute('intervenant_profile_mvc', [
                'id' => $this->authService->intervenant_id()
            ]);
        }

        $user = $this->intervenantService->getInfosIntervenant($id);

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        return $this->render('intervenants/profile.html.twig', [
            'auth' => $this->authService->check(),
            'user' => $user,
        ]);
    }

    #[Route('/intervenants-mvc/{id}/heures', name: 'intervenant_heures_mvc')]
    public function heures(int $id, Request $request): Response
    {
        if (!$this->authService->isAdmin() && $id != $this->authService->intervenant_id()) {
            return $this->redirectToRoute('intervenant_heures_mvc', [
                'id' => $this->authService->intervenant_id()
            ]);
        }

        $user = $this->intervenantService->getInfosIntervenant($id);

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        $prestations = $this->horaireService->getPrestationsParIntervenant($id);

        return $this->render('intervenants/hours/list.html.twig', [
            'auth' => $this->authService->check(),
            'user' => $user,
            'prestations' => $prestations,
            'isAdmin' => $this->authService->isAdmin(),
        ]);
    }

    #[Route('/intervenants-mvc/{id}/suivie', name: 'intervenant_suivie_mvc')]
    public function suivie(int $id, Request $request): Response
    {
        if (!$this->authService->isAdmin() && $id != $this->authService->intervenant_id()) {
            return $this->redirectToRoute('intervenant_suivie_mvc', [
                'id' => $this->authService->intervenant_id()
            ]);
        }

        $user = $this->intervenantService->getInfosIntervenant($id);

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        $familles = $this->familleService->getFamillesDeIntervenant($id);

        return $this->render('intervenants/hours/followup.html.twig', [
            'auth' => $this->authService->check(),
            'user' => $user,
            'isAdmin' => $this->authService->isAdmin(),
            'familles' => $familles,
            'nbrJourSaisie' => $this->horaireService->getNbrJourSaisie(),
            'editId' => $request->query->get('edite'),
        ]);
    }

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

    #[Route('/intervenants-mvc/recherche', name: 'intervenant_recherche_mvc')]
    public function recherche(Request $request): Response
    {
        $terme = $request->query->get('q', '');

        $users = [];
        if (!empty($terme)) {
            $users = $this->intervenantService->rechercherIntervenants($terme);
        }

        return $this->render('intervenants/recherche.html.twig', [
            'auth' => $this->authService->check(),
            'users' => $users,
            'terme' => $terme,
        ]);
    }
}
