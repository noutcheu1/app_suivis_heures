<?php

namespace App\Controller;

use App\Service\AuthService;
use App\Service\IntervenantService;
use App\Service\FamilleService;
use App\Service\HoraireinterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour les fonctionnalités administratives selon le pattern MVC
 */
final class AdminControllerMVC extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private IntervenantService $intervenantService,
        private FamilleService $familleService,
        private HoraireinterService $horaireService
    ) {}

    #[Route('/admin-mvc/dashboard', name: 'admin_dashboard_mvc')]
    public function dashboard(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $stats = [
            'total_intervenants' => $this->intervenantService->countIntervenants(),
            'total_familles' => $this->familleService->countFamilles(),
            'heures_ce_mois' => $this->horaireService->countHeuresMois(date('m/Y')),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'auth' => $this->authService->check(),
            'stats' => $stats,
        ]);
    }

    #[Route('/admin-mvc/intervenants', name: 'admin_intervenants_mvc')]
    public function listeIntervenants(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $intervenants = $this->intervenantService->getTousLesIntervenants();

        return $this->render('admin/intervenants/list.html.twig', [
            'auth' => $this->authService->check(),
            'users' => $intervenants,
        ]);
    }

    #[Route('/admin-mvc/familles', name: 'admin_familles_mvc')]
    public function listeFamilles(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $familles = $this->familleService->getToutesLesFamilles();

        return $this->render('admin/familles/list.html.twig', [
            'auth' => $this->authService->check(),
            'users' => $familles,
        ]);
    }

    #[Route('/admin-mvc/intervenant/{id}', name: 'admin_intervenant_detail_mvc')]
    public function detailIntervenant(int $id, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $intervenant = $this->intervenantService->getIntervenantParId($id);
        
        if (!$intervenant) {
            throw $this->createNotFoundException('Intervenant introuvable');
        }

        $prestations = $this->horaireService->getPrestationsParIntervenant($id);

        return $this->render('admin/intervenant_detail.html.twig', [
            'auth' => $this->authService->check(),
            'intervenant' => $intervenant,
            'prestations' => $prestations,
        ]);
    }

    #[Route('/admin-mvc/famille/{numFam}', name: 'admin_famille_detail_mvc')]
    public function detailFamille(string $numFam, Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $famille = $this->familleService->getFamilleParNumero($numFam);
        
        if (!$famille) {
            throw $this->createNotFoundException('Famille introuvable');
        }

        $prestations = $this->horaireService->getPrestationsParFamille($numFam);

        return $this->render('admin/famille_detail.html.twig', [
            'auth' => $this->authService->check(),
            'famille' => $famille,
            'prestations' => $prestations,
        ]);
    }

    #[Route('/admin-mvc/prepa-paie', name: 'admin_prepare_paie_mvc')]
    public function preparationPaie(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter le service de préparation paie
        $donneesPaie = [];

        return $this->render('admin/hours/paie.html.twig', [
            'auth' => $this->authService->check(),
            'mois' => $mois,
            'donneesPaie' => $donneesPaie,
        ]);
    }

    #[Route('/admin-mvc/prepa-paie/csv', name: 'admin_prepare_paie_csv_mvc')]
    public function preparationPaieCsv(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter la génération CSV
        $csvContent = "Préparation paie - {$mois}\n";
        $csvContent .= "Intervenant,Heures,Taux,Total\n";
        
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"prepa_paie_{$mois}.csv\"");
        
        return $response;
    }

    #[Route('/admin-mvc/recapitulatif', name: 'admin_recapitulatif_mvc')]
    public function recapitulatifHeures(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter le service de récapitulatif
        $recapitulatif = [];

        return $this->render('admin/hours/recap.html.twig', [
            'auth' => $this->authService->check(),
            'mois' => $mois,
            'recapitulatif' => $recapitulatif,
        ]);
    }

    #[Route('/admin-mvc/recapitulatif/csv', name: 'admin_recapitulatif_csv_mvc')]
    public function recapitulatifCsv(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $mois = $request->query->get('mois', date('m/Y'));
        
        // TODO: Implémenter la génération CSV
        $csvContent = "Récapitulatif heures - {$mois}\n";
        $csvContent .= "Intervenant,Famille,Date,Heures,Type\n";
        
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"recapitulatif_{$mois}.csv\"");
        
        return $response;
    }

    #[Route('/admin-mvc/fiches-vierges', name: 'admin_fiches_vierges_mvc')]
    public function fichesVierges(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $moisDebut = $request->query->get('mois_debut', date('m/Y'));
        $nbrMois = $request->query->get('nbr_mois', 12);

        return $this->render('admin/sheets/blank.html.twig', [
            'auth' => $this->authService->check(),
            'moisDebut' => $moisDebut,
            'nbrMois' => $nbrMois,
        ]);
    }

    #[Route('/admin-mvc/fiches-vierges/pdf', name: 'admin_fiches_vierges_pdf_mvc', methods: ['GET', 'POST'])]
    public function fichesViergesPdf(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        $moisDebut = $request->query->get('mois_debut', date('m/Y'));
        $nbrMois = $request->query->get('nbr_mois', 12);
        
        // TODO: Implémenter la génération PDF
        $response = new Response('Génération PDF à implémenter');
        
        return $response;
    }

    #[Route('/admin-mvc/signalements', name: 'admin_signalements_mvc')]
    public function signalements(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        // TODO: Implémenter le service de signalements
        $signalements = [];

        return $this->render('admin/hours/disputes.html.twig', [
            'auth' => $this->authService->check(),
            'signalements' => $signalements,
        ]);
    }

    #[Route('/admin-mvc/exceptions-facturation', name: 'admin_exceptions_facturation_mvc')]
    public function exceptionsFacturation(Request $request): Response
    {
        if (!$this->authService->check() || !$this->authService->isAdmin()) {
            return $this->redirectToRoute('app_login');
        }

        // TODO: Implémenter le service d'exceptions de facturation
        $exceptions = [];

        return $this->render('admin/exceptions-facturation.html.twig', [
            'auth' => $this->authService->check(),
            'exceptions' => $exceptions,
        ]);
    }
}
