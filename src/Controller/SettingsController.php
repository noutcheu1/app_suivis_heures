<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\IntervenantService;
use App\Service\User2Service;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private IntervenantService $intervenantService,
        private User2Service $user2Service
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

        $oldPass = $data['currentPassword'];
        $newPass = $data['newPassword'];

        $intervenant = $this->intervenantService->getIntervenantParId($id);
        if (!$intervenant || !$intervenant->getNumSs()) {
            return $this->json(['success' => false, 'message' => 'Intervenant introuvable'], 404);
        }

        $numSs = $intervenant->getNumSs();

        if (!$this->user2Service->authentifier($numSs, $oldPass)) {
            return $this->json(['success' => false, 'message' => 'Mot de passe actuel incorrect'], 401);
        }

        if (!$this->user2Service->mettreAJourMotDePasse($numSs, $newPass)) {
            return $this->json(['success' => false, 'message' => 'Erreur lors de la mise à jour'], 500);
        }

        return $this->json(['success' => true, 'message' => 'Mot de passe modifié avec succès']);
    }

    #[Route('/intervenants/{id}/settings', name: 'intervenant_settings')]
    public function intervenant_settings(int $id, Request $request): Response
    {
        if (!$this->authService->isAdmin() && $id !== $this->authService->intervenant_id()) {
            return $this->redirectToRoute('intervenant_settings', ['id' => $this->authService->intervenant_id()]);
        }

        $intervenant = $this->intervenantService->getIntervenantParId($id);

        return $this->render('dashboard/settings.html.twig', [
            'auth' => $this->authService->check(),
            'user' => $intervenant,
            'id' => $id,
            'type' => 'intervenant',
        ]);
    }
}
