<?php

namespace App\Controller;

use App\Security\Auth;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Page d'accueil redirige vers dashboard si connecté,
     * sinon vers la sélection du type (intervenant/famille)
     * 
     * ⚠ Cette route NE doit PAS s'appeler 'dashboard' DashboardController 
     * gère déjà la route '/' avec le nom 'dashboard'.
     * On sépare ici : '/' = type_selection UNIQUEMENT si pas connecté.
     */
    #[Route('/type-selection', name: 'type_selection')]
    public function index(Request $request): Response
    {
        if ($this->authService->check()) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->render(
            'type_selection_new.html.twig',
            ['auth' => false]
        );
    }

    #[Route('/set-type/{type}', name: 'set_type')]
    public function setType(string $type, Request $request): Response
    {
        if (!in_array($type, ['FAM', 'INTER'])) {
            throw $this->createNotFoundException('Type invalide');
        }

        $session = $request->getSession();
        $session->set('type', $type);

        return $this->redirectToRoute('app_login');
    }
}