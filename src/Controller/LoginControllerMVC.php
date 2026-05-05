<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\User2Service;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur refactorisé selon le pattern MVC
 * Remplace LoginController.php
 */
final class LoginControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private User2Service $user2Service
    ) {}

    #[Route('/login-mvc', name: 'app_login_mvc', methods: ['post', 'get'])]
    public function login(Request $request): Response
    {
        $session = $request->getSession();
        $type = $session->get('type');

        if ($this->authService->check()) {
            return $this->redirectToRoute('dashboard');
        }

        if (!$type) {
            return $this->redirectToRoute('type_selection');
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $id = $request->request->get('id');
            $password = $request->request->get('password');

            if ($this->authService->login($id, $password)) {
                return $this->redirectToRoute('dashboard');
            } else {
                $error = $type === 'INTER'
                    ? "Numéro de sécurité social ou mot de passe incorrect."
                    : "Numéro client ou mot de passe incorrect.";
            }
        }

        return $this->render('login.html.twig', [
            'auth' => $this->authService->check(),
            'type' => $type,
            'error' => $error
        ]);
    }

    #[Route('/logout-mvc', name: 'app_logout_mvc')]
    public function logout(Request $request): Response
    {
        $this->authService->logout();
        return $this->redirectToRoute('app_login_mvc');
    }

    #[Route('/api/register-mvc', name: 'api_register_mvc', methods: ['POST'])]
    public function apiRegister(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $type = $session->get('type'); // INTER ou FAM
        $data = json_decode($request->getContent(), true);

        $id = $data['id'] ?? null;
        $password = $data['password'] ?? null;
        $password2 = $data['password2'] ?? null;

        if (!$id || !$password || !$password2) {
            return $this->json(['success' => false, 'error' => 'Tous les champs sont requis.']);
        }

        if ($password !== $password2) {
            return $this->json(['success' => false, 'error' => 'Les mots de passe ne correspondent pas.']);
        }

        // Vérifier si l'utilisateur existe déjà
        if ($this->user2Service->identifiantExiste($id)) {
            return $this->json(['success' => false, 'error' => 'Utilisateur déjà inscrit']);
        }

        try {
            $this->user2Service->creerUtilisateur($id, $password);
        } catch (\Throwable $th) {
            return $this->json([
                'success' => false, 
                'error' => "Erreur serveur, réessayez. {$th->getMessage()}"
            ]);
        }

        return $this->json(['success' => true, 'message' => 'Inscription réussie.']);
    }

    #[Route('/register-mvc', name: 'app_register_mvc')]
    public function register(Request $request): Response
    {
        $session = $request->getSession();
        $type = $session->get('type'); // INTER ou FAM

        return $this->render('register.html.twig', [
            'auth' => $this->authService->check(),
            'type' => $type,
        ]);
    }
}
