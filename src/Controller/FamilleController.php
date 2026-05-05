<?php

namespace App\Controller;

use App\Security\Auth;
use App\PdoApp;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FamilleController extends AbstractController
{
    #[Route('/famille', name: 'famille')]
    public function famille(Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());

        if (!$auth->isAdmin()) {
            return $this->redirectToRoute('famille_panel', [
                'id' => $auth->famille_id()
            ]);
        }

        $familles = $pdo->getTousLesFamilles();
        
        return $this->render('famille/index.html.twig', [
            'auth' => $auth->check(),
            'users' => $familles,
            'controller_name' => 'FamilleController',
        ]);
    }

    #[Route('/famille/{id}', name: 'famille_id')]
    public function famille_id(string $id): Response
    {
        return $this->redirectToRoute('famille_panel', [
            'id' => $id
        ]);
    }

    #[Route('/famille/{id}/panel', name: 'famille_panel')]
    public function famille_panel(string $id, Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());

        $user = $pdo->getInfosFamille($id);

        if (!$user) {
            throw $this->createNotFoundException("Intervenant introuvable");
        }

        return $this->render('famille/panel.html.twig', [
            'auth' => $auth->check(),
            'id' => $id,
            'user' => $user,
        ]);
    }

    #[Route('/famille/{id}/profile', name: 'famille_profile')]
    public function famille_profile(string $id, Request $request, PdoApp $pdo): Response
    {
        $auth = new Auth($request->getSession());


        $user = $pdo->getInfosFamille($id);

        $avatar = "/assets/icons/famille-I.png";

        if ($user) {
            if ($user['PM'] != "" && $user['PGE'] != "") {
                $avatar = "/assets/icons/famille-GE&M.png";
            } else if ($user['PM'] != "" && $user['PGE'] == "") {
                $avatar = "/assets/icons/famille-M.png";
            } else if ($user['PM'] == "" && $user['PGE'] != "") {
                $avatar = "/assets/icons/famille-GE.png";
            }
        }
        
        return $this->render('famille/profile.html.twig', [
            'auth' => $auth->check(),
            'id' => $id,
            'user' => $user,
            'avatar' => $avatar
        ]);
    }
}
