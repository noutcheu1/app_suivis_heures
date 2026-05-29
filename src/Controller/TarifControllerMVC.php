<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\TarifService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TarifControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService  $authService,
        private TarifService $tarifService,
    ) {}

    #[Route('/admin-mvc/tarifs', name: 'admin_tarifs_mvc')]
    public function index(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $tarifActif   = $this->tarifService->getTarifActif();
        $tarifs       = $this->tarifService->getTousLesTarifs();
        $formDefaults = $this->tarifService->getFormDefaults($tarifActif);

        return $this->render('admin/tarifs/index.html.twig', [
            'auth'         => true,
            'tarifActif'   => $tarifActif,
            'tarifs'       => $tarifs,
            'formDefaults' => $formDefaults,
            'nbrPalierGE'  => $this->tarifService->getNbrPalierGE(),
            'nbrPalierM'   => $this->tarifService->getNbrPalierM(),
        ]);
    }

    #[Route('/admin-mvc/tarifs/creer', name: 'admin_tarifs_creer_mvc', methods: ['POST'])]
    public function creer(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('tarifs_creer', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_tarifs_mvc');
        }

        try {
            $this->tarifService->creerNouveauTarif($request->request->all());
            $this->addFlash('success', 'Nouveau tarif enregistré. Il s\'applique à partir du mois indiqué.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_tarifs_mvc');
    }
}
