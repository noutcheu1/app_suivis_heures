<?php

namespace App\Controller;

use App\Service\MdpOublieService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la gestion du mot de passe oublié selon le pattern MVC
 */
final class MdpOublieControllerMVC extends AbstractController
{
    public function __construct(
        private MdpOublieService $mdpOublieService
    ) {}

    #[Route('/mot-de-passe-oublie-mvc', name: 'mdp_oublie_mvc', methods: ['GET', 'POST'])]
    public function demandeReinitialisation(Request $request): Response
    {
        $error = null;
        $success = null;

        if ($request->isMethod('POST')) {
            $identifiant = $request->request->get('identifiant') ?? '';

            $statut = $identifiant
                ? $this->mdpOublieService->envoyerCodeReinitialisation($identifiant)
                : MdpOublieService::ENVOI_INTROUVABLE;

            if ($statut === MdpOublieService::ENVOI_LIMITE) {
                $error = 'Vous avez atteint la limite de 3 demandes par jour. Réessayez demain.';
            } else {
                // Sécurité : on ne révèle PAS si le compte existe. Que l'identifiant
                // existe ou non, on affiche la même page « un code a été envoyé si le
                // compte existe » (la saisie d'un code invalide échouera ensuite).
                return $this->redirectToRoute('mdp_oublie_verification_mvc', [
                    'identifiant' => $identifiant,
                    'envoye'      => 1,
                ]);
            }
        }

        return $this->render('mdp_oublie/demande.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/mot-de-passe-oublie-mvc/verification', name: 'mdp_oublie_verification_mvc', methods: ['GET', 'POST'])]
    public function verificationCode(Request $request): Response
    {
        $error = null;
        $identifiant = $request->query->get('identifiant');

        if ($request->isMethod('POST')) {
            $identifiant = $request->request->get('identifiant');
            $code = $request->request->get('code');
            $nouveauMotDePasse = $request->request->get('nouveau_mot_de_passe');
            $confirmation = $request->request->get('confirmation_mot_de_passe');

            if ($nouveauMotDePasse !== $confirmation) {
                $error = 'Les mots de passe ne correspondent pas.';
            } elseif ($this->mdpOublieService->reinitialiserMotDePasse($identifiant, $code, $nouveauMotDePasse)) {
                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
                return $this->redirectToRoute('app_login');
            } else {
                $error = 'Code invalide ou expiré.';
            }
        }

        return $this->render('mdp_oublie/verification.html.twig', [
            'error' => $error,
            'identifiant' => $identifiant,
            'envoye' => $request->query->get('envoye'),
        ]);
    }

    #[Route('/mot-de-passe-oublie-mvc/verifier-code', name: 'mdp_oublie_verifier_code_mvc', methods: ['POST'])]
    public function verifierCodeSeulement(Request $request): Response
    {
        $identifiant = $request->request->get('identifiant');
        $code = $request->request->get('code');

        if ($this->mdpOublieService->verifierCode($identifiant, $code)) {
            return $this->json(['success' => true]);
        } else {
            return $this->json(['success' => false, 'error' => 'Code invalide']);
        }
    }
}
