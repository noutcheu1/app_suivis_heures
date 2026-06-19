<?php

namespace App\Controller;

use App\Repository\FamilleRepository;
use App\Repository\IntervenantRepository;
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
        private AuthService           $authService,
        private UserSuiviService      $UserSuiviService,
        private IntervenantRepository $intervenantRepository,
        private FamilleRepository     $familleRepository,
        private LoggerInterface       $logger
    ) {}

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils
    ): Response {
        // Déjà connecté → on n'affiche pas le login, on redirige vers le bon espace
        if ($this->authService->check()) {
            if ($this->authService->isAdmin()) {
                return $this->redirectToRoute('admin_dashboard_mvc');
            }
            if ($this->authService->isIntervenant()) {
                return $this->redirectToRoute('intervenant_panel_mvc', [
                    'id' => $this->authService->intervenant_id(),
                ]);
            }
            if ($this->authService->isFamille()) {
                return $this->redirectToRoute('famille_panel_mvc');
            }
        }

        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $type =
            $request->query->get('type')
            ?? $request->request->get('type')
            ?? $request->getSession()->get('type');

        if ($type) {
            $request->getSession()->set('type', $type);
        }

        return $this->render('auth/login.html.twig', [
            'error'         => $error,
            'last_username' => $lastUsername,
            'type'          => $type,
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
        $session   = $request->getSession();
        $type      = $session->get('type');
        $data      = json_decode($request->getContent(), true);
        $id        = $data['id']        ?? null;
        $password  = $data['password']  ?? null;
        $password2 = $data['password2'] ?? null;
        $role      = match($type) { 'FAM' => 'famille', default => 'intervenant' };

        $this->logger->info('Tentative inscription API', ['id' => $id, 'type' => $type]);

        if (!$id || !$password || !$password2) {
            return $this->json(['success' => false, 'error' => 'Tous les champs sont requis.']);
        }

        if (strlen($password) < 8) {
            return $this->json(['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères.']);
        }

        if ($password !== $password2) {
            return $this->json(['success' => false, 'error' => 'Les mots de passe ne correspondent pas.']);
        }

        if ($errorDossier = $this->validerExistenceDossier($id, $type)) {
            return $this->json(['success' => false, 'error' => $errorDossier]);
        }

        if ($this->UserSuiviService->identifiantExiste($id)) {
            return $this->json(['success' => false, 'error' => 'Utilisateur déjà inscrit.']);
        }

        try {
            $this->UserSuiviService->creerUtilisateur($id, $password, $role);
            $this->logger->info('Utilisateur créé via API', ['id' => $id, 'role' => $role]);
        } catch (\Throwable $th) {
            $this->logger->error('Erreur création utilisateur', ['exception' => $th->getMessage()]);
            return $this->json(['success' => false, 'error' => "Erreur  : {$th->getMessage()}"]);
        }

        return $this->json(['success' => true, 'message' => 'Inscription réussie.']);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $session = $request->getSession();
        $type    = $session->get('type');

        if ($request->isMethod('POST')) {
            $id        = $request->request->get('id');
            $password  = $request->request->get('password');
            $password2 = $request->request->get('password2');
            $typePost  = $request->request->get('type');

            $this->logger->info('Tentative inscription FORM', ['id' => $id, 'type' => $typePost]);

            if ($typePost && in_array($typePost, ['INTER', 'FAM'], true)) {
                $session->set('type', $typePost);
                $type = $typePost;
            }

            $role  = match($type) { 'FAM' => 'famille', default => 'intervenant' };
            $error = null;

            if (!$id || !$password || !$password2) {
                $error = 'Tous les champs sont requis.';
            } elseif (strlen($password) < 8) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($password !== $password2) {
                $error = 'Les mots de passe ne correspondent pas.';
            } elseif ($errorDossier = $this->validerExistenceDossier($id, $type)) {
                $error = $errorDossier;
            } elseif ($this->UserSuiviService->identifiantExiste($id)) {
                $error = 'Un compte existe déjà avec cet identifiant.';
            } else {
                try {
                    $this->UserSuiviService->creerUtilisateur($id, $password, $role);
                    $this->logger->info('Utilisateur créé via FORM', ['id' => $id, 'role' => $role]);
                    return $this->redirectToRoute('app_login', ['type' => $type]);
                } catch (\Throwable $th) {
                    $error = "Erreur  : {$th->getMessage()}";
                    $this->logger->error('Erreur inscription FORM', ['exception' => $th->getMessage()]);
                }
            }

            return $this->render('auth/register.html.twig', [
                'auth'  => false,
                'type'  => $type,
                'error' => $error,
            ]);
        }

        return $this->render('auth/register.html.twig', [
            'auth' => $this->authService->check(),
            'type' => $type,
        ]);
    }

    /**
     * Vérifie que l'identifiant correspond à un dossier connu en base.
     * Retourne un message d'erreur ou null si OK.
     */
    private function validerExistenceDossier(string $id, ?string $type): ?string
    {
        if ($type === 'FAM') {
            if (!$this->familleRepository->findByNumero($id)) {
                return 'Aucune famille trouvée .';
            }
            return null;
        }

        // INTER ou type inconnu — cherche un intervenant
        $trouve = $this->intervenantRepository->findByNumSalarie($id)
               ?? $this->intervenantRepository->findByNumSs($id);

        if (!$trouve) {
            return 'Aucun dossier intervenant trouvé avec cet identifiant.';
        }

        return null;
    }
}
