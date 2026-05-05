<?php

namespace App\Controller;

use App\PdoApp;
use App\Security\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(PdoApp $pdo, Request $request): Response
    {

        $auth = new Auth($request->getSession());
        $session = $request->getSession();
        $type = $session->get('type');

        if ($auth->isAdmin()) {
            $user["nom_Candidats"] = "Admin";
            $user["prenom_Candidats"] = "Admin";
        } elseif ( $type == 'INTER' ) {
            return $this->redirectToRoute('intervenants');
        } elseif ( $type == 'FAM' ) {
            return $this->redirectToRoute('famille');
        } else {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('dashboard/index.html.twig', [
            'auth' => $auth->check(),
            'controller_name' => 'DashboardController',
            'user' => $user,
            'mode_no_dev' => !$_ENV['MODE_NO_DEV'],
            'isAdmin' => $auth->isAdmin()
        ]);
    }
}
