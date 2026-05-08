<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\UserSuiviService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private UserSuiviService $UserSuiviService,
        private LoggerInterface $logger
    ) {}

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils
    ): Response {

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $type =
            $request->query->get('type')
            ?? $request->request->get('type')
            ?? $request->getSession()->get('type');

        if ($type) {
            $request->getSession()->set('type', $type);
        }

        return $this->render('auth/login.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
            'type' => $type,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        $this->logger->info('Déconnexion utilisateur');

        throw new \LogicException('Intercepté par le firewall Symfony.');
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

        $this->logger->info('Tentative inscription API', [
            'id' => $id,
            'type' => $type,
        ]);

        if (!$id || !$password || !$password2) {

            $this->logger->warning('Champs inscription manquants');

            return $this->json([
                'success' => false,
                'error' => 'Tous les champs sont requis.'
            ]);
        }

        if ($password !== $password2) {

            $this->logger->warning('Mots de passe différents', [
                'id' => $id,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Les mots de passe ne correspondent pas.'
            ]);
        }

        if ($this->UserSuiviService->identifiantExiste($id)) {

            $this->logger->warning('Utilisateur déjà existant', [
                'id' => $id,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Utilisateur déjà inscrit'
            ]);
        }

        try {

            $this->UserSuiviService->creerUtilisateur($id, $password);

            $this->logger->info('Utilisateur créé avec succès', [
                'id' => $id,
            ]);

        } catch (\Throwable $th) {

            $this->logger->error('Erreur création utilisateur', [
                'id' => $id,
                'exception' => $th->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => "Erreur serveur, réessayez. {$th->getMessage()}"
            ]);
        }

        return $this->json([
            'success' => true,
            'message' => 'Inscription réussie.'
        ]);
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

            $this->logger->info('Tentative inscription FORM', [
                'id' => $id,
                'typePost' => $typePost,
            ]);

            if ($typePost && in_array($typePost, ['INTER', 'FAM'], true)) {
                $session->set('type', $typePost);
                $type = $typePost;
            }

            $error = null;

            if (!$id || !$password || !$password2) {

                $error = 'Tous les champs sont requis.';
                $this->logger->warning($error);

            } elseif (strlen($password) < 8) {

                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
                $this->logger->warning($error);

            } elseif ($password !== $password2) {

                $error = 'Les mots de passe ne correspondent pas.';
                $this->logger->warning($error);

            } elseif ($this->UserSuiviService->identifiantExiste($id)) {

                $error = 'Utilisateur déjà inscrit.';
                $this->logger->warning($error);

            } else {

                try {

                    $this->UserSuiviService->creerUtilisateur($id, $password);

                    $this->logger->info('Utilisateur créé via FORM', [
                        'id' => $id,
                    ]);

                    return $this->redirectToRoute('app_login');

                } catch (\Throwable $th) {

                    $error = "Erreur serveur, réessayez. {$th->getMessage()}";

                    $this->logger->error('Erreur inscription FORM', [
                        'exception' => $th->getMessage(),
                    ]);
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