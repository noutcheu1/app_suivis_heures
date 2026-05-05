<?php

namespace App\Controller;

use App\Security\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'type_selection')]
    public function index(Request $request): Response
    {
        $auth = new Auth($request->getSession());

        if ($auth->check()) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->render(
            'type_selection.html.twig', 
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

        return $this->redirectToRoute('app_login'); // redirection vers le login
    }

}
