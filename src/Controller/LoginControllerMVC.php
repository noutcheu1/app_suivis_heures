<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\User2Service;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LoginControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private User2Service $user2Service
    ) {}

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        if ($this->authService->check()) {
            return $this->redirectToRoute('dashboard');
        }

        $session = $request->getSession();
        $type = $session->get('type');

        // Allow selecting type via query param (?type=INTER or ?type=FAM)
        $typeParam = $request->query->get('type');
        if ($typeParam && in_array($typeParam, ['INTER', 'FAM'], true)) {
            $session->set('type', $typeParam);
            $type = $typeParam;
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $id = $request->request->get('id');
            $password = $request->request->get('password');

            if (!$type) {
                $type = $request->request->get('type');
                if ($type) {
                    $session->set('type', $type);
                }
            }

            if ($id && $password && $this->authService->login($id, $password)) {
                return $this->redirectToRoute('dashboard');
            }

            $error = $type === 'INTER'
                ? "Numéro de sécurité social ou mot de passe incorrect."
                : "Numéro client ou mot de passe incorrect.";
        }

        return $this->render('auth/login.html.twig', [
            'auth' => $this->authService->check(),
            'type' => $type,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $this->authService->logout();
        return $this->redirectToRoute('app_login');
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function apiRegister(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $type = $session->get('type');
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

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $session = $request->getSession();
        $type = $session->get('type');

        if ($request->isMethod('POST')) {
            $id = $request->request->get('id');
            $password = $request->request->get('password');
            $password2 = $request->request->get('password2');
            $typePost = $request->request->get('type');

            if ($typePost && in_array($typePost, ['INTER', 'FAM'], true)) {
                $session->set('type', $typePost);
                $type = $typePost;
            }

            $error = null;
            if (!$id || !$password || !$password2) {
                $error = 'Tous les champs sont requis.';
            } elseif (strlen($password) < 8) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($password !== $password2) {
                $error = 'Les mots de passe ne correspondent pas.';
            } elseif ($this->user2Service->identifiantExiste($id)) {
                $error = 'Utilisateur déjà inscrit.';
            } else {
                try {
                    $this->user2Service->creerUtilisateur($id, $password);
                    return $this->redirectToRoute('app_login');
                } catch (\Throwable $th) {
                    $error = "Erreur serveur, réessayez. {$th->getMessage()}";
                }
            }

            return $this->render('auth/register.html.twig', [
                'auth' => false,
                'type' => $type,
                'error' => $error,
            ]);
        }

        return $this->render('auth/register.html.twig', [
            'auth' => $this->authService->check(),
            'type' => $type,
        ]);
    }
}