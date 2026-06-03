<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\IntervenantService;
use App\Service\UserSuiviService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController extends AbstractController
{
    public function __construct(
        private AuthService        $authService,
        private IntervenantService $intervenantService,
        private UserSuiviService   $UserSuiviService
    ) {}

    #[Route('/api/changePassword/{id}', name: 'change_password_settings', methods: ['POST'])]
    public function change_password_settings(int $id, Request $request): Response
    {
        if (!$this->authService->isAdmin() && $id !== $this->authService->intervenant_id()) {
            return $this->json(['success' => false, 'message' => 'Accès interdit'], 403);
        }

        if (!$this->isCsrfTokenValid('change_password', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['currentPassword']) || empty($data['newPassword'])) {
            return $this->json(['success' => false, 'message' => 'Données manquantes'], 400);
        }

        $intervenant = $this->intervenantService->getIntervenantParId($id);
        if (!$intervenant) {
            return $this->json(['success' => false, 'message' => 'Intervenant introuvable'], 404);
        }

        // Priorité à numSalarie (identifiant de login), fallback sur numSs
        $username = $intervenant->getNumSalarie() ?? $intervenant->getNumSs();
        if (!$username) {
            return $this->json(['success' => false, 'message' => 'Identifiant de connexion introuvable'], 404);
        }

        if (!$this->UserSuiviService->authentifier($username, $data['currentPassword'])) {
            return $this->json(['success' => false, 'message' => 'Mot de passe actuel incorrect'], 401);
        }

        if (!$this->UserSuiviService->mettreAJourMotDePasse($username, $data['newPassword'])) {
            return $this->json(['success' => false, 'message' => 'Erreur lors de la mise à jour'], 500);
        }

        return $this->json(['success' => true, 'message' => 'Mot de passe modifié avec succès']);
    }

    #[Route('/intervenant/settings', name: 'intervenant_settings')]
    public function intervenant_settings(Request $request): Response
    {
        $id = $this->authService->intervenant_id();

        if (!$id && !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        // Admin peut voir les settings d'un intervenant via ?id=X
        if ($this->authService->isAdmin()) {
            $id = $request->query->getInt('id', 0) ?: $id;
        }

        $intervenant = $id ? $this->intervenantService->getIntervenantParId($id) : null;

        return $this->render('dashboard/settings.html.twig', [
            'auth' => $this->authService->check(),
            'user' => $intervenant,
            'id'   => $id,
            'type' => 'intervenant',
        ]);
    }
}
