<?php

namespace App\Controller;

use App\PdoApp;
use App\Security\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['post', 'get'])]
    public function login(Request $request, PdoApp $pdo): Response
    {

        $session = $request->getSession();
        $type = $session->get('type');

        $auth = new Auth($session);

        if ($auth->check()) {
            return $this->redirectToRoute('dashboard');
        }

        if (!$type) {
            return $this->redirectToRoute('type_selection');
        }
        
        $error = null;
        if ($request->isMethod('POST')) {
            
            $id = $request->request->get('id');
            $password = $request->request->get('password');

            $user = $pdo->getUser($id); // fonction PdoApp

            if ($user && password_verify($password, $user['mdp'])) {
                $session->set('username', $id);

                $i_id = null;
                $c_id = null;
                $f_id = null;

                // Définie l'id -1 et juste pour éviter que le champs soit pas vide
                if ($auth->isAdmin()) { 
                    $i_id = "-1";
                    $c_id = "-1";
                    $f_id = "-1";
                } else if ($type == "INTER") {
                    $i_id = $pdo->getIntervenantNumSS($id)['id'];
                    $c_id = $pdo->getIntervenantNumSS($id)['id_Canditat'];
                } else if ($type == "FAM") {
                    $f_id = $pdo->getNumsFamille($id)['numero_Famille'];
                }

                $session->set('intervenant_id', $i_id);
                $session->set('candidat_id', $c_id);
                $session->set('famille_id', $f_id);
                return $this->redirectToRoute('dashboard');
            } else {
                $error = $type === 'INTER'
                    ? "Numéro de sécurité social ou mot de passe incorrect."
                    : "Numéro client ou mot de passe incorrect.";
            }
        }

        return $this->render('login.html.twig', [
            'auth' => $auth->check(),
            'type' => $type,
            'error' => $error
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request) {
        $session = $request->getSession();
        $session->clear();
        return $this->redirectToRoute('app_login');
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function api_register(Request $request, PdoApp $pdo): JsonResponse
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

        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($type == "INTER" ) {
            $inscrit = $pdo->getIntervenantNumSS($id);
        } else {
            $inscrit = $pdo->getFamilleNumFam($id);
        }

        if (!$inscrit) {
            return $this->json([
                'success' => false, 
                'type'    => $type,
                'error'   => 'Identifiant incorect'
            ]);
        }

        $user = $pdo->getUser($id);

        if ($user) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur déjà inscrit'
            ]);
        }


        try {
            $success = $pdo->postUser($id, $hash);
        } catch (\Throwable $th) {
            return $this->json([
                'success' => false, 
                'error' => "Erreur serveur, réessayez. $th"
            ]);
        }
        

        if (!$success) {
            return $this->json(['success' => false, 'error' => 'Erreur serveur, réessayez.']);
        }

        return $this->json(['success' => true, 'message' => 'Inscription réussie.']);
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request) {
        $session = $request->getSession();
        $type = $session->get('type'); // INTER ou FAM
        $auth = new Auth($session);
        

        return $this->render('register.html.twig', [
            'auth' => $auth,
            'type' => $type,
        ]);
    }
}
