<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\EmailTemplateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Personnalisation des modèles d'email par l'admin (sujet + corps + variables).
 */
final class EmailTemplateControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService          $authService,
        private EmailTemplateService $emailTemplates,
    ) {}

    #[Route('/admin-mvc/emails', name: 'admin_emails_mvc', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('admin/emails.html.twig', [
            'auth'      => true,
            'templates' => $this->emailTemplates->listerPourAdmin(),
        ]);
    }

    #[Route('/admin-mvc/emails/enregistrer', name: 'admin_emails_save_mvc', methods: ['POST'])]
    public function enregistrer(Request $request): Response
    {
        if (!$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->isCsrfTokenValid('emails', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide. Réessayez.');
            return $this->redirectToRoute('admin_emails_mvc');
        }

        $cle   = (string) $request->request->get('cle', '');
        $sujet = trim((string) $request->request->get('sujet', ''));
        $corps = trim((string) $request->request->get('corps', ''));

        if ($cle === '' || $sujet === '' || $corps === '') {
            $this->addFlash('error', 'Le sujet et le message ne peuvent pas être vides.');
            return $this->redirectToRoute('admin_emails_mvc');
        }

        $this->emailTemplates->sauvegarder($cle, $sujet, $corps);
        $this->addFlash('success', 'Modèle d\'email enregistré.');

        return $this->redirectToRoute('admin_emails_mvc');
    }

    #[Route('/admin-mvc/emails/reinitialiser', name: 'admin_emails_reset_mvc', methods: ['POST'])]
    public function reinitialiser(Request $request): Response
    {
        if (!$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->isCsrfTokenValid('emails', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide. Réessayez.');
            return $this->redirectToRoute('admin_emails_mvc');
        }

        $this->emailTemplates->reinitialiser((string) $request->request->get('cle', ''));
        $this->addFlash('success', 'Modèle réinitialisé au texte par défaut.');

        return $this->redirectToRoute('admin_emails_mvc');
    }
}
