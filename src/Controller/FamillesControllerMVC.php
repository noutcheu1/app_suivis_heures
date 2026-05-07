<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\FamilleService;
use App\Service\HoraireinterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la gestion des familles selon le pattern MVC
 */
final class FamillesControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private FamilleService $familleService,
        private HoraireinterService $horaireService
    ) {}

    #[Route('/famille/mon-espace', name: 'famille_panel_mvc')]
    public function panel(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $numFam = $this->authService->famille_id();
        if (!$numFam) {
            return $this->redirectToRoute('dashboard');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $prestationsNonValidees = $this->horaireService->getPrestationsNonValidees($numFam);
        $prestationsValidees = $this->horaireService->getPrestationsParFamille($numFam);

        return $this->render('familles/dashboard.html.twig', [
            'auth' => true,
            'famille' => $famille,
            'prestationsNonValidees' => $prestationsNonValidees,
            'prestationsValidees' => $prestationsValidees,
        ]);
    }

    #[Route('/famille/mon-profil', name: 'famille_profile_mvc')]
    public function familleProfile(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $numFam = $this->authService->famille_id();
        if (!$numFam) {
            return $this->redirectToRoute('dashboard');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        return $this->render('familles/profile.html.twig', [
            'auth' => true,
            'famille' => $famille,
        ]);
    }

    #[Route('/familles-mvc', name: 'familles_mvc')]
    public function liste(Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $familles = $this->familleService->getToutesLesFamilles();

        return $this->render('admin/familles/list.html.twig', [
            'auth' => $this->authService->check(),
            'users' => $familles,
        ]);
    }

    #[Route('/familles-mvc/{numFam}', name: 'famille_detail_mvc')]
    public function detail(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        // Récupérer les prestations à valider
        $prestationsNonValidees = $this->horaireService->getPrestationsNonValidees($numFam);
        $prestationsValidees = $this->horaireService->getPrestationsParFamille($numFam);

        return $this->render('familles/detail.html.twig', [
            'auth' => $this->authService->check(),
            'famille' => $famille,
            'prestationsNonValidees' => $prestationsNonValidees,
            'prestationsValidees' => $prestationsValidees,
        ]);
    }

    #[Route('/familles-mvc/{numFam}/valider/{id}', name: 'famille_valider_prestation_mvc', methods: ['POST'])]
    public function validerPrestation(string $numFam, int $id, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->horaireService->validerPrestation($id, $numFam)) {
            $this->addFlash('success', 'Prestation validée avec succès');
        } else {
            $this->addFlash('error', 'Impossible de valider cette prestation');
        }

        return $this->redirectToRoute('famille_detail_mvc', ['numFam' => $numFam]);
    }

    #[Route('/familles-mvc/{numFam}/signaler/{id}', name: 'famille_signaler_prestation_mvc', methods: ['POST'])]
    public function signalerPrestation(string $numFam, int $id, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $remarque = $request->request->get('remarque');
        
        if ($this->horaireService->ajouterRemarque($id, $remarque, $numFam)) {
            $this->addFlash('success', 'Signalement enregistré avec succès');
        } else {
            $this->addFlash('error', 'Impossible d\'enregistrer le signalement');
        }

        return $this->redirectToRoute('famille_detail_mvc', ['numFam' => $numFam]);
    }

    #[Route('/familles-mvc/{numFam}/releves', name: 'famille_releves_mvc')]
    public function releves(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        // TODO: Implémenter le service pour les relevés mensuels familles
        $releves = []; // $this->releveFamilleService->getRelevesParFamille($numFam);

        return $this->render('familles/releves.html.twig', [
            'auth' => $this->authService->check(),
            'famille' => $famille,
            'releves' => $releves,
        ]);
    }

    #[Route('/familles-mvc/{numFam}/estimation-facture', name: 'famille_estimation_facture_mvc')]
    public function estimationFacture(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter le calcul d'estimation de facture
        $estimation = [
            'mois' => $mois,
            'montantTotal' => 0,
            'details' => []
        ];

        return $this->render('familles/estimation-facture.html.twig', [
            'auth' => $this->authService->check(),
            'famille' => $famille,
            'estimation' => $estimation,
        ]);
    }

    #[Route('/familles-mvc/{numFam}/avis', name: 'famille_avis_mvc', methods: ['POST'])]
    public function donnerAvis(string $numFam, Request $request): Response
    {
        if (!$this->authService->check()) {
            return $this->redirectToRoute('app_login');
        }

        $avis = [
            'ponctualite' => $request->request->get('ponctualite'),
            'relationnel' => $request->request->get('relationnel'),
            'respectHoraires' => $request->request->get('respectHoraires'),
            'qualiteTravail' => $request->request->get('qualiteTravail'),
        ];

        // TODO: Implémenter le service pour enregistrer les avis
        // $this->avisService->enregistrerAvis($numFam, $avis);

        $this->addFlash('success', 'Votre avis a été enregistré avec succès');

        return $this->redirectToRoute('famille_detail_mvc', ['numFam' => $numFam]);
    }
}
