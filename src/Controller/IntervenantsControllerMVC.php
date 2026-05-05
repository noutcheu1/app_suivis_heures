<?php

namespace App\Controller;

use App\Entity\Intervenant;
use App\Service\IntervenantService;
use App\Security\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur refactorisé selon le pattern MVC
 * Remplace IntervenantsController.php
 */
final class IntervenantsControllerMVC extends AbstractController
{
    public function __construct(
        private IntervenantService $intervenantService
    ) {}

    #[Route('/intervenants-mvc', name: 'intervenants_mvc')]
    public function intervenants(Request $request): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin()) {
            return $this->redirectToRoute('intervenant_panel_mvc', [
                'id' => $auth->intervenant_id()
            ]);
        }

        $users = $this->intervenantService->getTousLesIntervenants();

        return $this->render('intervenants/index.html.twig', [
            'auth' => $auth->check(),
            'users' => $users,
        ]);
    }

    #[Route('/intervenants-mvc/{id}/panel', name: 'intervenant_panel_mvc')]
    public function intervenantPanel(int $id, Request $request): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $id != $auth->intervenant_id()) {
            return $this->redirectToRoute('intervenant_panel_mvc', [
                'id' => $auth->intervenant_id()
            ]);
        }

        $user = $this->intervenantService->getInfosIntervenant($id);

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        return $this->render('intervenants/panel.html.twig', [
            'auth' => $auth->check(),
            'user' => $user,
        ]);
    }

    #[Route('/intervenants-mvc/{id}/profile', name: 'intervenant_profile_mvc')]
    public function intervenantProfile(int $id, Request $request): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $id != $auth->intervenant_id()) {
            return $this->redirectToRoute('intervenant_profile_mvc', [
                'id' => $auth->intervenant_id()
            ]);
        }

        $user = $this->intervenantService->getInfosIntervenant($id);

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        return $this->render('intervenants/profile.html.twig', [
            'auth' => $auth->check(),
            'user' => $user,
        ]);
    }

    #[Route('/intervenants-mvc/{id}/heures', name: 'intervenant_heures_mvc')]
    public function heures(int $id, Request $request): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $id != $auth->intervenant_id()) {
            return $this->redirectToRoute('intervenant_heures_mvc', [
                'id' => $auth->intervenant_id()
            ]);
        }

        $user = $this->intervenantService->getInfosIntervenant($id);

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        // Pour les heures, nous aurions besoin d'un service dédié
        $prestations = []; // TODO: Implémenter avec un PrestationService

        return $this->render('intervenants/heures.html.twig', [
            'auth' => $auth->check(),
            'user' => $user,
            'prestations' => $prestations,
        ]);
    }

    #[Route('/intervenants-mvc/{id}/suivie', name: 'intervenant_suivie_mvc')]
    public function suivie(int $id, Request $request): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $id != $auth->intervenant_id()) {
            return $this->redirectToRoute('intervenant_suivie_mvc', [
                'id' => $auth->intervenant_id()
            ]);
        }

        $user = $this->intervenantService->getInfosIntervenant($id);

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        return $this->render('intervenants/suivie.html.twig', [
            'auth' => $auth->check(),
            'user' => $user,
        ]);
    }

    #[Route('/intervenants-mvc/archiver/{id}', name: 'intervenant_archiver_mvc', methods: ['POST'])]
    public function archiver(int $id, Request $request): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin()) {
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
        $auth = new Auth($request->getSession());
        $terme = $request->query->get('q', '');

        $users = [];
        if (!empty($terme)) {
            $users = $this->intervenantService->rechercherIntervenants($terme);
        }

        return $this->render('intervenants/recherche.html.twig', [
            'auth' => $auth->check(),
            'users' => $users,
            'terme' => $terme,
        ]);
    }
}
