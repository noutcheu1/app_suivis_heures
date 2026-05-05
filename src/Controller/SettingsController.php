<?php

namespace App\Controller;

use App\Security\Auth;
use App\PdoApp;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController extends AbstractController
{
    #[Route('/api/changePassword/{id}', name: 'change_password_settings', methods: ['POST'])]
    public function change_password_settings(
        int $id,
        PdoApp $pdo,
        Request $request
    ): Response {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $id != $auth->intervenant_id()) {
            return $this->json([
                'success' => false,
                'message' => "Accès interdit "
            ], 403);
        }

        if (!$this->isCsrfTokenValid(
            'change_password',
            $request->headers->get('X-CSRF-TOKEN')
        )) {
            return $this->json([
                'success' => false,
                'message' => 'Token CSRF invalide'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (
            empty($data['currentPassword']) ||
            empty($data['newPassword'])
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Données manquantes'
            ], 400);
        }

        $oldPass = $data['currentPassword'];
        $newPass = $data['newPassword'];

        $intervenant = $pdo->getIntervenantNumInter($id);
        $user = $pdo->getUser($intervenant['numSS_Candidats'] ?? null);

        if (!$user || !password_verify($oldPass, $user['mdp'])) {
            return $this->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect'
            ], 401);
        }

        $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);

        $success = $pdo->putNewMdp($intervenant['numSS_Candidats'], $hashedPassword);
        
        if (!$success) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur c\'est produite veullier réessayer plus tard'
            ], 401);
        }

        return $this->json([
            'success' => false,
            'message' => 'Mot de passe modifié avec succès'
        ], 200);
    }

    #[Route('/intervenants/{id}/settings', name: 'intervenant_settings')]
    public function intervenant_settings(int $id, PdoApp $pdo, Request $request): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin() && $id != $auth->intervenant_id()) {
            return $this->redirectToRoute('intervenant_settings', [
                'id' => $auth->intervenant_id()
            ]);
        }

        $user = $pdo->getInfosIntervenant($id);

        return $this->render('settings/index.html.twig', [
            'auth' => $auth,
            'user' => $user,
            'id' => $id,
            'type' => "intervenant"
        ]);
    }
}
